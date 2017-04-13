<?php

/**
 * Representation of an incoming, server-side request.
 *
 * @package    framewub/http-message
 * @author     Wubbo Bos <wubbo@wubbobos.nl>
 * @copyright  Copyright (c) Wubbo Bos
 * @license    GPL
 * @link       https://github.com/wubmeister/framewub
 */

namespace Framewub\Http\Message;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Framewub\Http\Message\Stream\PHPInput;

/**
 * Server-side request
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * The cookie parameters in this request
     *
     * @var array
     */
    protected $cookies;

    /**
     * The query parameters in this request
     *
     * @var array
     */
    protected $query;

    /**
     * The parsed body of this request
     *
     * @var array
     */
    protected $parsedBody;

    /**
     * The attributes of this request
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Flag to check if the body has been parsed into parsedBody
     *
     * @var bool
     */
    protected $isBodyParsed = false;

    /**
     * The uploaded files
     *
     * @var array
     */
    protected $uploadedFiles = [];

    /**
     * ServerRequest constructor
     */
    public function __construct($attributes = null)
    {
        $this->requestTarget = $_SERVER['REQUEST_URI'];
        $this->uri = new Uri($this->requestTarget);
        $this->cookies = $_COOKIE;
        $this->query = $_GET;
        $this->parsedBody = $_POST;

        if ($attributes && is_array($attributes)) {
            $this->attributes = $attributes;
        }

        foreach ($_FILES as $key => $file) {
            if (is_array($file['name'])) {
                $this->uploadedFiles[$key] = [];
                foreach ($file['name'] as $i => $name) {
                    $this->uploadedFiles[$key][] = new UploadedFile([
                        'name' => $name,
                        'type' => $file['type'][$i],
                        'size' => $file['size'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error' => $file['error'][$i]
                    ]);
                }
            } else {
                $this->uploadedFiles[$key] = new UploadedFile($file);
            }
        }
    }

    /**
     * Retrieve server parameters.
     *
     * @return array
     */
    public function getServerParams()
    {
        return $_SERVER;
    }

    /**
     * Retrieve cookies.
     *
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }

    /**
     * Return an instance with the specified cookies.
     *
     * @param array $cookies
     *   Array of key/value pairs representing cookies.
     *
     * @return static
     */
    public function withCookieParams(array $cookies)
    {
        $newRequest = clone $this;
        $newRequest->cookies = $cookies;
        return $newRequest;
    }

    /**
     * Retrieve query string arguments.
     *
     * @return array
     */
    public function getQueryParams()
    {
        return $this->query;
    }

    /**
     * Return an instance with the specified query string arguments.
     *
     * @param array $query
     *   Array of query string arguments, typically from $_GET.
     *
     * @return static
     */
    public function withQueryParams(array $query)
    {
        $newRequest = clone $this;
        $newRequest->query = $query;
        return $newRequest;
    }

    /**
     * Retrieve normalized file upload data.
     *
     * @return array
     *   An array tree of UploadedFileInterface instances; an empty array MUST
     *   be returned if no data is present.
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * Create a new instance with the specified uploaded files.
     *
     * @param array $uploadedFiles
     *   An array tree of UploadedFileInterface instances.
     *
     * @return static
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        $newRequest = clone $this;
        $newRequest->uploadedFiles = $uploadedFiles;
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
            $this->body = new PHPInput();
        }
        return $this->body;
    }

    /**
     * Retrieve any parameters provided in the request body.
     *
     * @return null|array|object
     *   The deserialized body parameters, if any. These will typically be an
     *   array or object.
     */
    public function getParsedBody()
    {
        $contentType = implode(',', $this->getHeader('Content-Type'));
        if ($contentType == 'application/json' && !$this->isBodyParsed) {
            $body = $this->getBody();
            $this->parsedBody = json_decode((string)$body, true);
        }
        return $this->parsedBody;
    }

    /**
     * Return an instance with the specified body parameters.
     *
     * @param null|array|object $data
     *   The deserialized body data. This will typically be in an array or object.
     *
     * @return static
     * @throws \InvalidArgumentException if an unsupported argument type is
     *   provided.
     */
    public function withParsedBody($data)
    {
        $newRequest = clone $this;
        $newRequest->parsedBody = $data;
        return $newRequest;
    }

    /**
     * Retrieve attributes derived from the request.
     *
     * @return array
     *   Attributes derived from the request.
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve a single derived request attribute.
     *
     * @param string $name
     *   The attribute name.
     * @param mixed $default
     *   Default value to return if the attribute does not exist.
     *
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : $default;
    }

    /**
     * Return an instance with the specified derived request attribute.
     *
     * @param string $name
     *   The attribute name.
     * @param mixed $value
     *   The value of the attribute.
     *
     * @return static
     */
    public function withAttribute($name, $value)
    {
        $newRequest = clone $this;
        $newRequest->attributes[$name] = $value;
        return $newRequest;
    }

    /**
     * Return an instance with the specified derived request attributes.
     *
     * @param array $attributes
     *   The attributes.
     *
     * @return static
     */
    public function withAttributes($attributes)
    {
        $newRequest = clone $this;
        $newRequest->attributes = $attributes;
        return $newRequest;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     *
     * @param string $name
     *   The attribute name.
     *
     * @return static
     */
    public function withoutAttribute($name)
    {
        $newRequest = clone $this;
        if (isset($newRequest->attributes[$name])) {
            unset($newRequest->attributes[$name]);
        }
        return $newRequest;
    }
}