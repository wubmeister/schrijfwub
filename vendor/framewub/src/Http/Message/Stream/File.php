<?php

/**
 * Value object representing a file stream.
 *
 * @package    framewub/http-message
 * @author     Wubbo Bos <wubbo@wubbobos.nl>
 * @copyright  Copyright (c) Wubbo Bos
 * @license    GPL
 * @link       https://github.com/wubmeister/framewub
 */

namespace Framewub\Http\Message\Stream;

/**
 * File stream class
 */
class File extends AbstractStream
{
    /**
     * Opens a file with the specified file name
     *
     * @param string $filename
     *   The filename
     * @param string $mode
     *   The filemode, compatible with fopen
     */
    public function __construct(string $filename, string $mode)
    {
        $this->filename = $filename;
        $this->file = fopen($this->filename, $mode);

        if (substr($mode, -1) == '+') {
            $this->readable = true;
            $this->writable = true;
        } else if ($mode[0] == 'r') {
            $this->readable = true;
            $this->writable = false;
        } else {
            $this->readable = false;
            $this->writable = true;
        }
    }
}