<?php

namespace App\Storage;

use App\Database;

/**
 * Abstract base class for storages
 */
class AbstractStorage
{
    /**
     * The database
     * @var $db;
     */
    protected $db;

    /**
     * The table name
     * @var string
     */
    protected $table;

    /**
     * Constructs the storage object with a database adapter
     *
     * @param App\Database $db The database
     */
    public function __construct(Database $db)
    {
        $this->db = $db;

        if (!$this->table) {
            throw new \Exception('No table name is set');
        }
    }

    /**
     * Finds an object by ID
     *
     * @param int $id The ID
     * @return array The object or NULL if no object exists with that ID
     */
    public function find($id)
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `id` = :id";
        return $this->db->fetchRow($sql, [ 'id' => (int)$id ]);
    }

    /**
     * Fetches all objects in the table
     *
     * @return array The fetched objects
     */
    public function fetchAll()
    {
        $sql = "SELECT * FROM `{$this->table}`";
        return $this->db->fetchAll($sql);
    }

    /**
     * Inserts an object in the database
     *
     * @param array $columns The columns to insert
     * @return int The ID of the newly created object or NULL if the method fails
     */
    public function insert($columns)
    {
        return $this->db->insert($this->name, $columns);
    }

    /**
     * Updates a object with the given ID
     *
     * @param array $columns The columns with updated values
     * @param int $id The ID of the object to update
     * @return int The number of affected rows
     */
    public function update($columns, $id)
    {
        return $this->db->update($this->table, $id, $columns);
    }

    /**
     * Gets the last error message from the database
     *
     * @return string The error message
     */
    public function getErrorMessage()
    {
        return $this->db->getErrorMessage();
    }
}
