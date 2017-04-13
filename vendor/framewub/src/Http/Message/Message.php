<?php

/**
 * Representation of a HTTP message.
 *
 * @package    framewub/http-message
 * @author     Wubbo Bos <wubbo@wubbobos.nl>
 * @copyright  Copyright (c) Wubbo Bos
 * @license    GPL
 * @link       https://github.com/wubmeister/framewub
 */

namespace Framewub\Http\Message;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Framewub\Http\Message\Stream\AbstractStream;

/**
 * HTTP message
 */
class Message implements MessageInterface
{
    /**
     * The protocol version
     *
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * The headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * The body
     *
     * @var Psr\Http\Message\StreamInterface
     */
    protected $body;

    /**
     * The headers
     *
     * @var array
     */
    protected $headerLCMap = [];

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * @return string
     *   HTTP protocol version.
     */
    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * @param string $version
     *   HTTP protocol version
     *
     * @return static
     */
    public function withProtocolVersion($version)
    {
        $newRequest = clone $this;
        $newRequest->protocolVersion = $version;
        return $newRequest;
    }

    /**
     * Retrieves all message header values.
     *
     * @return string[][]
     *  Returns an associative array of the message's headers. Each
     *  key is a header name, and each value is an array of strings
     *  for that header.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name
     *   Case-insensitive header field name.
     * @return bool
     *   Returns true if any header names match the given header name using a
     *   case-insensitive string comparison. Returns false if no matching header
     *   name is found in the message.
     */
    public function hasHeader($name)
    {
        $lc = strtolower($name);
        $key = isset($this->headerLCMap[$lc]) ? $this->headerLCMap[$lc] : $name;
        return isset($this->headers[$key]);
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * @param string $name
     *   Case-insensitive header field name.
     * @return string[]
     *   An array of string values as provided for the given header. If the
     *   header does not appear in the message, this method returns an empty
     *   array.
     */
    public function getHeader($name)
    {
        $lc = strtolower($name);
        $key = isset($this->headerLCMap[$lc]) ? $this->headerLCMap[$lc] : $name;
        return isset($this->headers[$key]) ? $this->headers[$key] : [];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * @param string $name
     *  Case-insensitive header field name.
     * @return string
     *  A string of values as provided for the given header concatenated
     *  together using a comma. If the header does not appear in the message,
     *  this method returns an empty string.
     */
    public function getHeaderLine($name)
    {
        return implode(',', $this->getHeader($name));
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * @param string $name
     *   Case-insensitive header field name.
     * @param string|string[] $value
     *   Header value(s).
     *
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withHeader($name, $value)
    {
        $newRequest = clone $this;

        $key = strtolower($name);
        if (isset($newRequest->headerLCMap[$key])) {
            $name = $newRequest->headerLCMap[$key];
        } else {
            $newRequest->headerLCMap[$key] = $name;
        }

        $newRequest->headers[$name] = is_array($value) ? $value : [ $value ];

        return $newRequest;
    }

    /**
     * Return an instance with the specified header appended with the given
     * value.
     *
     * @param string $name
     *   Case-insensitive header field name to add.
     * @param string|string[] $value
     *   Header value(s).
     *
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value)
    {
        $newRequest = clone $this;

        $key = strtolower($name);
        if (isset($newRequest->headerLCMap[$key])) {
            $name = $newRequest->headerLCMap[$key];
        } else {
            $newRequest->headerLCMap[$key] = $name;
        }

        if (isset($newRequest->headers[$name])) {
            if (!is_array($value)) {
                $value = [ $value ];
            }
            $newRequest->headers[$name] = array_merge($newRequest->headers[$name], $value);
        } else {
            $newRequest->headers[$name] = is_array($value) ? $value : [ $value ];
        }

        return $newRequest;
    }

    /**
     * Return an instance without the specified header.
     *
     * @param string $name
     *   Case-insensitive header field name to remove.
     *
     * @return static
     */
    public function withoutHeader($name)
    {
        $newRequest = clone $this;

        $key = strtolower($name);
        if (isset($newRequest->headerLCMap[$key])) {
            $name = $newRequest->headerLCMap[$key];
            unset($newRequest->headerLCMap[$key]);
        }

        if (isset($newRequest->headers[$name])) {
            unset($newRequest->headers[$name]);
        }

        return $newRequest;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface
     *   Returns the body as a stream.
     */
    public function getBody()
    {
        if (!$this->body) {
            $this->body = new AbstractStream();
        }
        return $this->body;
    }

    /**
     * Return an instance with the specified message body.
     *
     * @param StreamInterface $body
     *   Body.
     *
     * @return static
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body)
    {
        $newRequest = clone $this;
        $newRequest->body = $body;
        return $newRequest;
    }
}