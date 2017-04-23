<?php

namespace App\Blog\Controller;

use App\Router\AbstractRouter;
use App\Template;
use App\Database;
use App\Blog\Storage\Articles as ArticleStorage;

/**
 * This 'controller' does everything blog-related
 */
class Blog extends AbstractRouter
{
    /**
     * The database
     * @var Database
     */
    protected $db;

    /**
     * The article storage
     * @var ArticleStorage
     */
    protected $articleStorage;

    /**
     * The layout
     * @var Template
     */
    protected $layout;

    /**
     * Constructs the blog controller with a Database object
     *
     * @param Database $db The database
     */
    public function __construct(ArticleStorage $storage)
    {
        $this->articleStorage = $storage;
        $this->layout = new Template('layout');
    }

    /**
     * Handles a URL
     *
     * @param string $url
     * @return array The route match
     */
    protected function matchAgainst($url)
    {
        $chunks = $this->chunkify($url);

        switch ($chunks[0]) {
            case '':
                $this->showIndex();
                break;

            case 'archive':
                if (count($chunks) > 1) {
                    if (is_numeric($chunks[1]) && strlen($chunks[1]) == 4) {
                        $this->showArchive($chunks[1], (count($chunks) > 2) ? $chunks[2] : null);
                    } else {
                        $this->showTagArchive($chunks[1]);
                    }
                } else {
                    $this->showArchive();
                }
                break;

            case 'edit':
                $this->editArticle(count($chunks) > 1 ? $chunks[1] : null);
                break;

            default:
                $cat = $this->articleStorage->findCategoryBySlug($chunks[0]);
                if ($cat) {
                    $this->showCategory($cat);
                } else {
                    if (count($chunks) > 1 && $chunks[1] == 'comments') {
                        shift($chunks);
                        $match['callable'] = new Comments($this->db);
                        $match['tail'] = implode('/', $chunks);
                    } else {
                        $this->showArticle($chunks[0]);
                    }
                }
        }

        $this->response->getBody()->write($this->layout->render());

        return [ 'response' => $this->response ];
    }

    /**
     * Shows the index page
     */
    protected function showIndex()
    {
        $result = $this->articleStorage->listArticles("`id`, `title`, `slug`, `lead`, `image_url`, `published`", "FROM `blog_articles`", " WHERE `published` < NOW()");

        $template = new Template('blog/index');
        $template->archiveList = $this->articleStorage->getArchiveList();
        $template->categories = $this->articleStorage->getCategoryArchiveList();
        $content = $template->render($result);

        $this->layout->content = $template->render($result);
    }

    /**
     * Shows the archive page
     */
    protected function showArchive($year = null, $month = null)
    {
        $where = "";
        if ($year) {
            $year = (int)$year;
            $thisYear = idate('Y');
            if ($month) {
                $month = (int)$month;
                $thisMonth = idate('n');
                $nextYear = $year;
                $nextMonth = $month + 1;
                if ($nextMonth > 12) {
                    $nextYear++;
                    $nextMonth = 1;
                }

                if ($year >= $thisYear && $month > $thisMonth) {
                    $where = null;
                } else if ($nextYear == $thisYear && $nextMonth == $thisMonth) {
                        $where = sprintf("WHERE `published` BETWEEN '%04d-%02d-01 00:00:00' AND NOW()", $year, $month);
                } else {
                    $where = sprintf("WHERE `published` BETWEEN '%04d-%02d-01 00:00:00' AND '%04d-%02d-01 00:00:00'", $year, $month, $nextYear, $nextMonth);
                }
            } else {
                $nextYear = $year + 1;
                if ($year > $thisYear) {
                    $where = null;
                } else if ($nextYear == $thisYear) {
                    $where = sprintf("WHERE `published` BETWEEN '%04d-01-01 00:00:00' AND NOW()", $year);
                } else {
                    $where = sprintf("WHERE `published` BETWEEN '%04d-01-01 00:00:00' AND '%04d-01-01 00:00:00'", $year, $nextYear);
                }
            }
        }

        if ($where == null) {
            throw new NotFoundException();
        }

        $result = $this->articleStorage->listArticles("`id`, `title`, `slug`, `lead`, `image_url`, `published`", "FROM `blog_articles`", $where);
        $result['year'] = $year;
        $result['month'] = $month;

        $template = new Template('blog/archive');
        $template->archiveList = $this->articleStorage->getArchiveList();
        $template->categories = $this->articleStorage->getCategoryArchiveList();

        $this->layout->content = $template->render($result);
    }

    /**
     * Shows the the tag archive
     */
    protected function showTagArchive($tagSlug)
    {
        $result = $this->articleStorage->fetchByTag($tagSlug);
        $result['tag'] = $tagSlug;

        $template = new Template('blog/tag-archive');
        $this->layout->content = $template->render($result);
    }

    /**
     * Shows a form to edit an article
     */
    protected function editArticle($articleSlug = null)
    {
        checkLogin();

        $article = null;
        $formSuccess = false;

        $result = [
            'error' => '',
            'formSuccess' => false,
            'article' => $article,
            'linkedCategories' => []
        ];

        if ($articleSlug) {
            $article = $this->articleStorage->fetchArticleBySlug($articleSlug, true);

            if (!$article) {
                throw new NotFoundException();
            }

            $result['article'] = $article;
        }

        if ($_POST) {
            // TODO: validate POST etc.
            $values = $_POST;
            if (!$values['published']) {
                $values['published'] = date('Y-m-d H:i:s');
            }
            $values['created'] = date('Y-m-d H:i:s');
            $values['slug'] = generateSlug($values['title']);

            if (isset($values['categories'])) {
                $categories = $values['categories'];
                unset($values['categories']);
            }

            if ($article) {
                $this->articleStorage->update($values, (int)$article['id']);
                $result['article'] = array_merge($article, $values);
                $result['formSuccess'] = true;
            } else {
                $id = $this->articleStorage->insert($values);
                if (!$id) {
                    $result['error'] = $this->articleStorage->getErrorMessage();
                    $result['article'] = $values;
                } else {
                    $article = $result['article'] = $this->articleStorage->find($id);
                    $result['formSuccess'] = true;
                }
            }

            if ($result['formSuccess'] && $article['id'] && $categories !== null) {
                $this->articleStorage->updateCategories($article['id'], $categories);
            }

        }

        $result['categoryOptions'] = $this->articleStorage->getCategories();

        if ($article && $article['id']) {
            $linkedCats = $this->articleStorage->getCategories($article['id']);
            $result['linkedCategories'] = array_map(function ($cat) { return $cat['id']; }, $linkedCats);
        }

        $template = new Template('blog/edit-article');
        $this->layout->title = 'Edit article';
        $this->layout->content = $template->render($result);
    }

    /**
     * Shows the articles from a certain category
     */
    protected function showCategory($category)
    {
        $result = $this->articleStorage->fetchByCategory($category['id']);

        $template = new Template('blog/category');
        $template->category = $category;
        $template->archiveList = $this->articleStorage->getArchiveList();
        $template->categories = $this->articleStorage->getCategoryArchiveList();

        $this->layout->content = $template->render($result);
    }

    /**
     * Shows a single article
     */
    protected function showArticle($articleSlug)
    {
        $article = $this->articleStorage->fetchArticleBySlug($articleSlug, isLoggedIn());

        if (!$article) {
            throw new NotFoundException();
        }
        $template = new Template('blog/article');
        $template->archiveList = $this->articleStorage->getArchiveList();
        $template->categories = $this->articleStorage->getCategoryArchiveList();
        $this->layout->content = $template->render([ 'article' => $article ]);
    }
}