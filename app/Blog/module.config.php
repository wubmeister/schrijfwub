<?php

return [
    'services' => [
        'BlogModule' => function ($container) {
            $container->set('ArticleStorage', function ($container) {
                $db = $container->get('Database');
                return new App\Blog\Storage\Articles($db);
            });

            $container->set('CommentStorage', function ($container) {
                $db = $container->get('Database');
                return new App\Blog\Storage\Comments($db);
            });

            $container->set('BlogController', function ($container) {
                $storage = $container->get('ArticleStorage');
                return new App\Blog\Controller\Blog($storage);
            });

            $container->set('CommentsController', function ($container) {
                $storage = $container->get('ArticleStorage');
                return new App\Blog\Controller\Comments($storage);
            });

            return new \App\Blog\Module($container);
        }
    ]
];
