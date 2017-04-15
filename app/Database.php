<?php

namespace App;

use PDO;

class Database
{
    /**
     * The PDO adapter
     * @var PDO
     */
    protected $pdo;

    /**
     * The error info
     * @var array
     * @link http://php.net/manual/en/pdo.errorinfo.php
     */
    protected $errorInfo = [ null, null, '' ];

    /**
     * Constructs the Database object and connects to the database
     */
    public function __construct()
    {
        $dateTime = new DateTime(); date
        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=blog', 'bloguser', 'eD9JEajJLzqskyYJWVDX');
        $this->pdo->exec("SET time_zone='" . date('P') . "'");
    }

    /**
     * Executes a query on the database
     *
     * @param string $sql The SQL query
     * @param array $params Parameters to bind
     * @return PDOStatement The resulting statement. Returns NULL if executing the query failed
     */
    public function query($sql, $params = null)
    {
        $stmt = $this->pdo->prepare($sql);
        if ($params) {
            foreach ($params as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
        }

        if (!$stmt) {
            $this->errorInfo = $this->pdo->errorInfo();
        }
        if (!$stmt->execute()) {
            $this->errorInfo = $stmt->errorInfo();
        }
        return $stmt;
    }

    /**
     * Executes an SQL query and fetches all the resulting rows
     *
     * @param string $sql The SQL query
     * @param array $params Parameters to bind
     * @return array The resulting rows
     */
    public function fetchAll($sql, $params = null)
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Executes an SQL query and fetches a single resulting row
     *
     * @param string $sql The SQL query
     * @param array $params Parameters to bind
     * @return array The resulting row as an associative array
     */
    public function fetchRow($sql, $params = null)
    {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Inserts data in the database
     *
     * @param string $table The table name
     * @param array $data The columns to insert
     * @return int The inserted ID. Returns NULL if the insertion failed
     */
    public function insert($table, $data)
    {
        $keys = array_keys($data);
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $keys) . "`) VALUES (:" . implode(', :', $keys) . ")";
        if ($this->query($sql, $data)) {
            return $this->pdo->lastInsertId();
        }

        return null;
    }

    /**
     * Updates data in the database
     *
     * @param string $table The table name
     * @param int $id The ID of the row to update
     * @param array $data The columns to update
     * @return int The number of rows updated
     */
    public function update($table, $id, $data)
    {
        $keys = array_keys($data);
        $sql = "UPDATE `{$table}` SET ";
        foreach ($keys as $index => $key) {
            $sql .= ($index > 0 ? ", " : "") . "`{$key}` = :{$key}";
        }

        $sql .= " WHERE `id` = :id";
        $data['id'] = $id;

        if ($stmt = $this->query($sql, $data)) {
            return $stmt->rowCount();
        }

        return 0;
    }

    /**
     * Gets the last error message
     *
     * @return string The error message
     */
    public function getErrorMessage()
    {
        return $this->errorInfo[2];
    }

    /**
     * Gets the last insert ID
     *
     * @return int The last insert ID
     */
    public function getLastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}