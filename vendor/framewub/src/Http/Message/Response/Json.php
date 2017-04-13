<?php

/**
 * Class to represent a JSON response
 *
 * @package    framewub/http-message
 * @author     Wubbo Bos <wubbo@wubbobos.nl>
 * @copyright  Copyright (c) Wubbo Bos
 * @license    GPL
 * @link       https://github.com/wubmeister/framewub
 */

namespace Framewub\Http\Message\Response;

use Framewub\Http\Message\Response;
use Framewub\Http\Message\Stream\PHPOutput;

class Json extends Response
{
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
        parent::__construct($bodyString, $statusCode);
        $this->headers['Content-Type'] = [ 'application/json' ];
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
            $this->body = new PHPOutput('json_encode');
        }
        return $this->body;
    }
}
