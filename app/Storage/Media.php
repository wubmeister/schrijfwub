<?php

namespace App\Storage;

use App\Database;

/**
 * Class to manage media in the database
 */
class Media
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
}
