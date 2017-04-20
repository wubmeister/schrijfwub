<?php

namespace App\Storage;

/**
 * Class to manage comments on articles
 */
class Commenters extends AbstractStorage
{
    /**
     * The table name
     * @var string
     */
    protected $table = 'blog_commenters';

    /**
     * Finds a commenter by e-mail address
     *
     * @param string $email The e-mail address
     * @return array The commenter or NULL if no commenter exists with that e-mail address
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM `{$this->name}` WHERE `email` = :email";
        return $this->fetchRow($sql, [ 'email' => $email ]);
    }

    /**
     * Creates a new commenter with the specified e-mail address
     *
     * @param string $email The commenter's e-mail address
     * @param string $name The commenter's name
     * @return array The newly created commenter
     */
    public function createWithEmailAndName($email, $name)
    {
        $id = $this->insert([ 'email' => $email, 'name' => $name ]);
        if (!$id) {
            return null;
        }

        return $this->find($id);
    }
}