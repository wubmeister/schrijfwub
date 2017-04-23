<?php

namespace App\Blog;

use App\Module as BaseModule;

class Module extends BaseModule
{
    protected function matchAgainst($url)
    {
        $chunks = $this->chunkify($url);
        $match = [];

        if ($chunks[0] == 'commenter') {
            $match['callable'] = $this->container->get('CommentsController');
            $match['route_tail'] = '/' . implode('/', array_slice($chunks, 1));
        } else {
            $match['callable'] = $this->container->get('BlogController');
        }

        return $match;
    }
}
