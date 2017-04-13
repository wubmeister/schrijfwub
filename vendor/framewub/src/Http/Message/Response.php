<?php

/**
 * Representation of an outgoing, server-side response.
 *
 * @package    framewub/http-message
 * @author     Wubbo Bos <wubbo@wubbobos.nl>
 * @copyright  Copyright (c) Wubbo Bos
 * @license    GPL
 * @link       https://github.com/wubmeister/framewub
 */

namespace Framewub\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Framewub\Http\Message\Stream\PHPOutput;

class Response extends Message implements ResponseInterface
{
    /**
     * The response status code
     *
     * @var int
     */
    protected $statusCode;

    /**
     * The reason phrase
     *
     * @var string
     */
    protected $reasonPhrase = '';

    /**
     * Constructs a response with specified body and status code
     *
     * @param string $bodyString
     *   OPTIONAL. The body of the response
     * @param int $statusCode
     *   OPTIONAL. The status code
     */
    public function __construct($bodyString = null, $statusCode = 200)
    {
        $this->statusCode = $statusCode;
        if ($bodyString) {
            $this->getBody()->write($bodyString);
        }
    }

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int
     *   Status code.
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason
     * phrase.
     *
     * @param int $code
     *   The 3-digit integer result code to set.
     * @param string $reasonPhrase
     *   The reason phrase to use with the provided status code; if none is
     *   provided, implementations MAY use the defaults as suggested in the HTTP
     *   specification.
     *
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        if (!isset(self::$reasonPhrases[$code])) {
            throw new InvalidArgumentException("{$code} is not a valid HTTP status code");
        }
        $newRequest = clone $this;
        $newRequest->statusCode = $code;
        $newRequest->reasonPhrase = $reasonPhrase;

        return $newRequest;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * @return string
     *   Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase()
    {
        return $this->reasonPhrase ? $this->reasonPhrase :
            (isset(self::$reasonPhrases[$this->statusCode]) ? self::$reasonPhrases[$this->statusCode] : '');
    }

    /**
     * Gets the body of the message.
     *
     * @return PHPOutput
     *   Returns the body as a stream.
     */
    public function getBody()
    {
        if (!$this->body) {
            $this->body = new PHPOutput();
        }
        return $this->body;
    }

    /**
     * Flushes the response
     */
    public function flush()
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $header => $value) {
            header($header . ': ' . implode(',', $value));
        }
        $this->getBody()->close();
    }

    /**
     * The RFC-7231 reason phrases
     *
     * @var array
     */
    protected static $reasonPhrases = [
        100 => "Continue",
        101 => "Switching Protocols",
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        307 => "Temporary Redirect",
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Payload Too Large",
        414 => "URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Range Not Satisfiable",
        417 => "Expectation Failed",
        426 => "Upgrade Required",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported"
    ];
}
