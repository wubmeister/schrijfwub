<?php

namespace App\Router;

use App\Controller\Blog as BlogController;

class Main extends AbstractRouter
{
	protected function matchAgainst($url)
	{
		global $db;

		$chunks = $this->chunkify($url);
		$match = [];

		switch ($chunks[0]) {
			case 'login':
				$match['callable'] = 'handleLogin';
				$match['route_tail'] = '/' . implode('/', array_slice($chunks, 1));
				break;

			case 'logout':
				$match['callable'] = 'handleLogin';
				$match['route_tail'] = '/' . implode('/', array_slice($chunks, 1));
				break;

			case 'commenter':
				$match['callable'] = new CommentsController($db);
				$match['route_tail'] = '/' . implode('/', array_slice($chunks, 1));
				break;

			case 'mail':
				$match['response'] = $this->response;
				// sendMail('Testmail', 'testmail', 'Wubbo Bos', 'wubbo@wubbobos.nl', [ 'foo' => 'Bar' ]);
				break;

			// case 'media':
			// 	shift($chunks);
			// 	$match['callable'] = new MediaController();
			// 	$match['route_tail'] = '/' . implode($chunks);
			// 	break;

			// case 'admin':
			// 	shift($chunks);
			// 	$match['callable'] = new AdminRouter();
			// 	$match['route_tail'] = '/' . implode($chunks);
			// 	break;

			default:
				$match['callable'] = new BlogController($db);
				break;
		}

		return $match;
	}
}
