<?php

namespace App;

class MainModule extends Module
{
	protected function matchAgainst($url)
	{
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

			default:
				$match['callable'] = $this->container->get('BlogModule');
				break;
		}

		return $match;
	}
}
