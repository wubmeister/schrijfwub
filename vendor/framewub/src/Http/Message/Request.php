<?php

/**
 * Representation of an outgoing, client-side request.
 *
 * @package    framewub/http-message
 * @author     Wubbo Bos <wubbo@wubbobos.nl>
 * @copyright  Copyright (c) Wubbo Bos
 * @license    GPL
 * @link       https://github.com/wubmeister/framewub
 */

namespace Framewub\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Client-side request
 */
class Request extends Message implements RequestInterface
{
    /**
     * The request target
     *
     * @var string
     */
    protected $requestTarget = '/';

    /**
     * The request method
     *
     * @var string
     */
    protected $method = 'GET';

    /**
     * The URI
     *
     * @var Uri
     */
    protected $uri;

    /**
     * Retrieves the message's request target.
     *
     * @return string
     */
    public function getRequestTarget()
    {
        return $this->requestTarget;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * @param mixed $requestTarget
     *
     * @return static
     */
    public function withRequestTarget($requestTarget)
    {
        $newRequest = clone $this;
        $newRequest->requestTarget = $requestTarget;
        return $newRequest;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string
     *   Returns the request method.
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * @param string $method
     *   Case-sensitive method.
     *
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        $method = strtoupper($method);
        if (!in_array($method, [ 'GET', 'POST', 'DELETE', 'PUT', 'OPTIONS' ])) {
            throw new InvalidArgumentException("{$method} is not a valid HTTP method");
        }

        $newRequest = clone $this;
        $newRequest->method = $method;

        return $newRequest;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @return UriInterface
     *  Returns a UriInterface instance representing the URI of the request.
     */
    public function getUri()
    {
        if (!$this->uri) {
            $this->uri = new Uri('/');
        }
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * @param UriInterface $uri
     *   New request URI to use.
     * @param bool $preserveHost
     *   Preserve the original state of the Host header.
     *
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $host = $uri->getHost();
        if ($host && !$preserveHost) {
            $newRequest = $this->withHeader('Host', $host);
        } else {
            $newRequest = clone $this;
        }
        $newRequest->uri = $uri;

        return $newRequest;
    }
}