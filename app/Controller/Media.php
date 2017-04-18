<?php

namespace App\Controller;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use App\Router\AbstractRouter;
use App\Database;
use App\Storage\Media as MediaStorage;

/**
 * This 'controller' does everything blog-related
 */
class Media extends AbstractRouter
{
    /**
     * The database
     * @var Database
     */
    protected $db;

    /**
     * The media storage
     * @var MediaStorage
     */
    protected $mediaStorage;

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
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->mediaStorage = new MediaStorage($db);
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
        if ($this->request->getMethod() == 'POST') {
            $this->handleUpload();
        } else if ($url == '/browser') {
            $this->showBrowser();
        } else {
            $this->generatePreview();
        }

        $this->response->getBody()->write($this->layout->render());

        return [ 'response' => $this->response ];
    }

    protected function handleUpload(){}
    protected function showBrowser(){}
    protected function generatePreview(){}
}