<?php

namespace App;

/**
 * Class to manage blog articles in the database
 */
class ArticleStorage
{
    /**
     * The database
     * @var $db;
     */
    protected $db;

    /**
     * Constructs the storage object with a database adapter
     *
     * @param App\Database $db The database
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

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

        $sql .= " LIMIT {$pagination['first_item_index']}, {$pagination['items_per_page']}";

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
    public function fetchArticleBySlug($slug)
    {
        $sql = "SELECT * FROM `blog_articles` WHERE `slug` = :slug AND `published` < NOW() ORDER BY `published` DESC LIMIT 1";
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
}