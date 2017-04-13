<?php

/**
 * Representation of the php://input stream
 *
 * @package    framewub/http-message
 * @author     Wubbo Bos <wubbo@wubbobos.nl>
 * @copyright  Copyright (c) Wubbo Bos
 * @license    GPL
 * @link       https://github.com/wubmeister/framewub
 */

namespace Framewub\Http\Message\Stream;

class PHPInput extends AbstractStream
{
    /**
     * String for mocking the stream contents when in CLI mode
     *
     * @var string
     */
    protected static $mcc = 'Mock content';

    /**
     * Mock the content of future stream instances in CLI mode
     *
     * @param string $string
     *   The mocked content
     */
    public static function mockCliContents(string $string)
    {
        self::$mcc = $string;
    }

    /**
     * Constructs an instance by opening the real php://input stream when in
     * server-mode, or mock it using the mock content when in CLI-mode
     */
    public function __construct()
    {
        if (php_sapi_name() == 'cli') {
            $this->filename = 'php://memory';
            $this->file = fopen($this->filename, 'w+');
            fwrite($this->file, self::$mcc);
            fseek($this->file, 0);
        } else {
            $this->filename = 'php://input';
            $this->file = fopen($this->filename, 'r');
        }
    }
}
