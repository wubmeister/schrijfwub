<?php

namespace App\Blog\Storage;

use App\Database;
use App\Storage\AbstractStorage;

/**
 * Class to manage blog articles in the database
 */
class Articles extends AbstractStorage
{
    /**
     * The table name
     * @var string
     */
    protected $table = 'blog_articles';

    /**
     * Lists all the articles which match the specified conditions
     *
     * @param string $columns The columns in the SQL query
     * @param string $from The 'FROM' clause in the SQL query, including joins
     * @param string $where The 'WHERE' clase in the SQL query
     * @param array $params Additional parameters to bind to the statement
     * @return array A two-item array: [ 'articles' => [ ... ], 'pagination' => [ ... ] ]
     */
    public function listArticles($columns, $from, $where = '', $params = null)
    {
        $countSql = "SELECT COUNT(*) AS `count` {$from} {$where}";
        $sql = "SELECT {$columns} {$from} {$where}";

        $countRow = $this->db->fetchRow($countSql, $params);
        $pagination = $this->getPagination($countRow ? (int)$countRow['count'] : 0);

        $sql .= " ORDER BY `published` DESC LIMIT {$pagination['first_item_index']}, {$pagination['items_per_page']}";

        $articles = $this->db->fetchAll($sql, $params);

        return [
            'articles' => $articles,
            'pagination' => $pagination
        ];
    }

    /**
     * Fetches one article by it's slug
     *
     * @param string $slug The article's slug
     * @return array The article or NULL if no article was found
     */
    public function fetchArticleBySlug($slug, $ignorePubDate = false)
    {
        $sql = "SELECT * FROM `blog_articles` WHERE `slug` = :slug ";
        if (!$ignorePubDate) $sql .= "AND `published` < NOW() ";
        $sql .= "ORDER BY `published` DESC LIMIT 1";
        return $this->db->fetchRow($sql, [ 'slug' => $slug ]);
    }

    /**
     * Fetches all the articles from a certain category
     *
     * @param int $categoryId The category ID
     */
    public function fetchByCategory($categoryId)
    {
        $from = "FROM `blog_category_has_articles` AS `link` LEFT JOIN `blog_articles` AS `a` ON `a`.`id` = `link`.`article_id`";
        $where = "WHERE `link`.`category_id` = :category_id AND `a`.`published` < NOW()";
        $params = [ 'category_id' => (int)$categoryId ];
        return $this->listArticles("`a`.`id`, `a`.`title`, `a`.`slug`, `a`.`lead`, `a`.`image_url`, `a`.`published`", $from, $where, $params);
    }

    /**
     * Fetches all the articles with a certain tag
     *
     * @param int $tagSlug The tag slug
     */
    public function fetchByTag($tagSlug)
    {
        $from = "FROM `blog_categories` AS `c` LEFT JOIN `blog_category_has_articles` AS `link` ON `link`.`category_id` = `c`.`id` LEFT JOIN `blog_articles` AS `a` ON `a`.`id` = `link`.`article_id` AND `a`.`published` < NOW()";
        $where = "WHERE `c`.`type` = 'tag' AND `c`.`slug` = :slug";
        $params = [ 'slug' => $tagSlug ];
        return $this->listArticles("`a`.`id`, `a`.`title`, `a`.`slug`, `a`.`lead`, `a`.`image_url`, `a`.`published`", $from, $where, $params);
    }

    /**
     * Gets pagination information based on item count
     *
     * @param int $itemCount The total number of items
     * @return array The pagination info
     */
    protected function getPagination($itemCount)
    {
        $numPerPage = 20;
        $currentPage = 1;
        $numPages = ceil($itemCount / $numPerPage);

        $pagination = [
            'first' => 1,
            'last' => $numPages,
            'current' => $currentPage,
            'page_count' => $numPages,
            'items_per_page' => $numPerPage,
            'first_item_index' => ($currentPage - 1) * $numPerPage,
            'last_item_index' => $currentPage * $numPerPage - 1
        ];

        return $pagination;
    }

    /**
     * Returns an archive list, listing all the years and months when blog articles
     * were published and the number of articles published in that year or month
     *
     * @return array The archive list: [ $year => [ 'count' => $yearCount, 'months' => [ $month => [ 'count' => $monthCount, 'name' => 'month name' ] ] ] ]
     */
    function getArchiveList()
    {
        $sql = "SELECT LEFT(`published`, 7) AS `year_month`, COUNT(*) AS `count` FROM `blog_articles` WHERE `published` < NOW() GROUP BY `year_month` ASC";
        $rows = $this->db->fetchAll($sql);

        $years = [];
        foreach ($rows as $row) {
            list($year, $month) = explode('-', $row['year_month']);
            $time = strtotime($row['year_month'].'-01 00:00:00');

            if (!isset($years[$year])) {
                $years[$year] = [ 'count' => 0, 'months' => [] ];
            }
            $years[$year]['count'] += (int)$row['count'];
            $years[$year]['months'][$month] = [ 'count' => (int)$row['count'], 'name' => strftime('%B', $time) ];
        }

        return $years;
    }

    /**
     * Returns a list with all the categories containing blog articles, along with
     * the number of articles posted per category
     *
     * @return array The categories
     */
    public function getCategoryArchiveList()
    {
        $sql = "SELECT `c`.*, COUNT(*) AS `count`
            FROM `blog_categories` AS `c`
            LEFT JOIN `blog_category_has_articles` AS `link` ON `link`.`category_id` = `c`.`id`
            LEFT JOIN `blog_articles` AS `a` ON `a`.`id` = `link`.`article_id`
            WHERE `a`.`published` < NOW()
            GROUP BY `c`.`id`";
        $list = $this->db->fetchAll($sql);

        return $list;
    }

    /**
     * Gets a category by its slug
     *
     * @param string $slug The category slug
     * @return array The category from teh database or NULL if no category exists with that slug
     */
    public function findCategoryBySlug($slug)
    {
        return $this->db->fetchRow("SELECT * FROM `blog_categories` WHERE `type` = 'cat' AND `slug` = :slug", [ 'slug' => $slug ]);
    }

    /**
     * Updates an article's categories
     *
     * @param int $articleId The article ID
     * @param array $categoryes The categories. All items prefixed with 'id:'
     *    will be treated as category IDs. Everything else will be treated as
     *    new category names.
     */
    public function updateCategories($articleId, $categories)
    {
        $linkedIds = [];

        $linkSql = "INSERT IGNORE INTO `blog_category_has_articles` (`category_id`, `article_id`) VALUES (:category_id, :article_id)";
        $linkParams = [ 'article_id' => $articleId ];
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
        $params = [ 'article_id' => $articleId ];
        if (count($linkedIds)) {
            $sql .= " AND `category_id` NOT IN (" . implode(',', $linkedIds) . ")";
        }

        $this->db->query($sql, $params);
    }

    /**
     * Gets all the existing categories
     *
     * @param int $articleId If specified, this method will find all categories
     *    linked to the article with that ID. If omitted, this method will find
     *    all categories.
     * @return array The category rows
     */
    public function getCategories($articleId = null)
    {
        if ($articleId) {
            $sql = "SELECT * FROM `blog_category_has_articles` AS `link` LEFT JOIN `blog_categories` AS `c` ON `c`.`id` = `link`.`category_id`
                WHERE `link`.`article_id` = :article_id";
            return $this->db->fetchAll($sql, [ 'article_id' => $articleId ]);
        }

        return $this->db->fetchAll("SELECT * FROM `blog_categories` WHERE `type` = 'cat' ORDER BY `name`");
    }
}