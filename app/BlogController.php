<?php

namespace App;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This 'controller' does everything blog-related
 */
class BlogController
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
     * The request
     * @var Template
     */
    protected $request;

    /**
     * Constructs the blog controller with a Database object
     *
     * @param Database $db The database
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->articleStorage = new ArticleStorage($db);
        $this->layout = new Template('layout');
    }

    /**
     * Invokes the controller
     *
     * @param RequestInterface $request The request
     * @param ResponseInterface $response The response
     * @param callable $next The next middleware in the chain
     * @return ResponseInterface The response
     */
    public function __invoke(RequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        $pathname = $request->getUri()->getPath();
        $chunks = explode('/', ltrim($pathname, '/'));

        $this->request = $request;

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
                $cat = $this->db->fetchRow("SELECT * FROM `blog_categories` WHERE `type` = 'cat' AND `slug` = :slug", [ 'slug' => $chunks[0] ]);
                if ($cat) {
                    $this->showCategory($cat);
                } else {
                    $this->showArticle($chunks[0]);
                }
        }

        $response->getBody()->write($this->layout->render());

        if ($next) {
            $response = $next($request, $response);
        }

        return $response;
    }

    /**
     * Shows the index page
     */
    protected function showIndex()
    {
        $result = $this->articleStorage->listArticles("`id`, `title`, `slug`, `lead`, `published`", "FROM `blog_articles`");

        $template = new Template('index');
        $template->layout = $this->layout;
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
            if ($month) {
                $month = (int)$month;
                $nextYear = $year;
                $nextMonth = $month + 1;
                if ($nextMonth > 12) {
                    $nextYear++;
                    $nextMonth = 1;
                }

                $where = sprintf("WHERE `published` BETWEEN '%04d-%02d-01 00:00:00' AND '%04d-%02d-01 00:00:00'", $year, $month, $nextYear, $nextMonth);
            } else {
                $nextYear = $year + 1;
                $where = sprintf("WHERE `published` BETWEEN '%04d-01-01 00:00:00' AND '%04d-01-01 00:00:00'", $year, $nextYear);
            }
        }

        $result = $this->articleStorage->listArticles("`id`, `title`, `slug`, `lead`, `published`", "FROM `blog_articles`", $where);
        $result['year'] = $year;
        $result['month'] = $month;

        $template = new Template('archive');
        $template->layout = $this->layout;
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

        $template = new Template('tag-archive');
        $template->layout = $this->layout;
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
            $article = $this->articleStorage->fetchArticleBySlug($articleSlug);

            if (!$article) {
                throw new NotFoundException();
            }

            $result['article'] = $article;
        }

        if ($_POST) {
            // TODO: validate POST etc.
            $values = $_POST;
            $values['published'] = date('Y-m-d H:i:s');
            $values['created'] = date('Y-m-d H:i:s');
            $values['slug'] = generateSlug($values['title']);

            if (isset($values['categories'])) {
                $categories = $values['categories'];
                unset($values['categories']);
            }

            if ($article) {
                $this->db->update('blog_articles', (int)$article['id'], $values);
                $result['article'] = array_merge($article, $values);
                $result['formSuccess'] = true;
            } else {
                $id = $this->db->insert('blog_articles', $values);
                if (!$id) {
                    $result['error'] = $this->db->getErrorMessage();
                    $result['article'] = $values;
                } else {
                    $sql = "SELECT * FROM `blog_articles` WHERE `id` = :id";
                    $article = $result['article'] = $this->db->fetchRow($sql, [ 'id' => $id ]);
                    $result['formSuccess'] = true;
                }
            }

            if ($result['formSuccess'] && $article['id'] && $categories !== null) {
                $linkedIds = [];

                $linkSql = "INSERT IGNORE INTO `blog_category_has_articles` (`category_id`, `article_id`) VALUES (:category_id, :article_id)";
                $linkParams = [ 'article_id' => $article['id'] ];
                $addSql = "INSERT INTO `blog_categories` (`type`, `name`, `slug`, `created`) VALUES ('cat', :name, :slug, NOW())";
                $addParams = [ ];

                foreach ($categories as $category) {
                    $category = trim($category);
                    if ($category) {
                        if (substr($category, 0, 3) == 'id:' && is_numeric(substr($category, 3))) {
                            $linkParams['category_id'] = (int)substr($category, 3);
                        } else {
                            $addParams['name'] = $category;
                            $addParams['slug'] = generateSlug($category);
                            $this->db->query($addSql, $addParams);
                            $linkParams['category_id'] = $this->db->getLastInsertId();
                        }

                        if ($linkParams['category_id']) {
                            $linkedIds[] = $linkParams['category_id'];
                            $this->db->query($linkSql, $linkParams);
                        }
                    }
                }

                $sql = "DELETE FROM `blog_category_has_articles` WHERE `article_id` = :article_id";
                $params = [ 'article_id' => $article['id'] ];
                if (count($linkedIds)) {
                    $sql .= " AND `category_id` NOT IN (" . implode(',', $linkedIds) . ")";
                }

                $this->db->query($sql, $params);
            }

        }

        $catSql = "SELECT * FROM `blog_categories` WHERE `type` = 'cat' ORDER BY `name`";
        $result['categoryOptions'] = $this->db->fetchAll($catSql);

        if ($article && $article['id']) {
            $linkCatSql = "SELECT * FROM `blog_category_has_articles` AS `link` LEFT JOIN `blog_categories` AS `c` ON `c`.`id` = `link`.`category_id`
                WHERE `link`.`article_id` = :article_id";
            $linkedCats = $this->db->fetchAll($linkCatSql, [ 'article_id' => $article['id'] ]);
            $result['linkedCategories'] = array_map(function ($cat) { return $cat['id']; }, $linkedCats);
        }

        $template = new Template('edit-article');
        $this->layout->title = 'Edit article';
        $template->layout = $this->layout;
        $this->layout->content = $template->render($result);
    }

    /**
     * Shows the articles from a certain category
     */
    protected function showCategory($category)
    {
        $result = $this->articleStorage->fetchByCategory($category['id']);

        $template = new Template('category');
        $template->layout = $this->layout;
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
        $article = $this->articleStorage->fetchArticleBySlug($articleSlug);

        if (!$article) {
            throw new NotFoundException();
        }
        $template = new Template('article');
        $template->layout = $this->layout;
        $this->layout->content = $template->render([ 'article' => $article ]);
    }
}