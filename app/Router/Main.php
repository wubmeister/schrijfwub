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
				shift($chunks);
				$match['callable'] = 'handleLogin';
				$match['route_tail'] = '/' . implode($chunks);
				break;

			case 'logout':
				shift($chunks);
				$match['callable'] = 'handleLogin';
				$match['route_tail'] = '/' . implode($chunks);
				break;

			case 'commenter':
				shift($chunks);
				$match['callable'] = new CommentsController($db);
				$match['route_tail'] = '/' . implode($chunks);
				break;

			case 'mail':
				$match['response'] = $this->response;
				sendMail('Testmail', 'testmail', 'Wubbo Bos', 'wubbobos@gmail.com', [ 'foo' => 'Bar' ]);
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
