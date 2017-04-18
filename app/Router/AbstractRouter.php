<?php

namespace App\Router;

/**
 * Abstract class for middleware classes which can route a URL to a piece of code
 */
abstract class AbstractRouter
{
	/**
	 * The request
	 * @var ServerRequestInterface
	 */
	protected $request;

	/**
	 * The response
	 * @var ResponseInterface
	 */
	protected $response;

	/**
	 * Tries to match the (remaing part of the) request URL to a piece of middleware.
	 * If there is a match, the middleware wil be invoked.
	 *
	 * It can also happen that a route returns a Response instance. In that case,
	 * that response instance will be used as the response to the request
	 *
	 * @param ServerRequestInterface $request The request
	 * @param ResponseInterface $response The response
	 * @param callable $next The next callable in the chain
	 * @return ResponseInterface The new response
	 */
	public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next = null)
	{
        $this->request = $request;
        $this->response = $response;

		$tail = $request->getAttribute('route_tail');
		if (!$tail) {
			$tail = $request->getUrl()->getPath();
		}

		if ($match = $this->matchAgainst($tail)) {
			if (isset($match['response'])) {
				$response = $match['response'];
			} else if (isset($match['callable'])) {
				$middleware = $match['callable'];
				unset($match['callable']);

				if (!is_callable($middleware)) {
					if (!class_exists($middleware)) {
						throw new Exception('Invalid middleware: ' . $middleware);
					}
					$middleware = new $middleware();
				}

				$response = $middleware($request->withAttributes($match), $reponse);
			} else {
				throw new Exception('Invalid router match');
			}
		}

		if ($next) {
			$response = $next($request, $reponse);
		}

		return $response;
	}

	/**
	 * Splits a URL into chunks (which were separated by a slash)
	 *
	 * @param string $url The URL to split
	 * @return arrat The array of chunks
	 */
	protected function chunkify($url)
	{
		return explode('/', ltrim($url, '/'));
	}

	/**
	 * This method should check te passed URL and see if it can map it to a piece of code.
	 *
	 * @param string $url The URL
	 * @return array|null The match. The match array should countain a 'callable'
	 *    or 'response' key and optionally a 'route_tail' key for the rest of the URL.
	 */
	abstract protected function matchAgainst($url);
}
