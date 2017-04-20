<?php

namespace App\Storage;

/**
 * Class to manage comments on articles
 */
class Comments extends AbstractStorage
{
    /**
     * The table name
     * @var string
     */
    protected $table = 'blog_comments';

    /**
     * Gets all the IP's a commenter has posted from
     *
     * @param int $commenterId The commenter's ID
     * @return array An array with IP addresses
     */
    public function getIpListByCommenter($commenterId)
    {
        $sql = "SELECT GROUP_CONCAT(`ip` SEPARATOR ',') AS `ips` FROM `{$this->name}` WHERE `commenter_id` = :commenter_id AND `is_visible` = 1";
        $row = $this->db->fetchRow($sql, [ 'commenter_id' => $commenterId ]);

        return $row ? explode(',', $row['ips']) : [];
    }
}