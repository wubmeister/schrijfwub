<?php

/**
 * Value object representing a file uploaded through an HTTP request.
 *
 * @package    framewub/http-message
 * @author     Wubbo Bos <wubbo@wubbobos.nl>
 * @copyright  Copyright (c) Wubbo Bos
 * @license    GPL
 * @link       https://github.com/wubmeister/framewub
 */

namespace Framewub\Http\Message;

use Psr\Http\Message\UploadedFileInterface;
use Framewub\Http\Message\Stream\File;

class UploadedFile implements UploadedFileInterface
{
    /**
     * The original file name
     *
     * @var string
     */
    protected $name = null;

    /**
     * The mime type
     *
     * @var string
     */
    protected $type;

    /**
     * The file size
     *
     * @var int
     */
    protected $size;

    /**
     * The temporary location of the uploaded file
     *
     * @var string
     */
    protected $tmp_name;

    /**
     * The new location of the uploaded file after it's been moved by moveTo()
     *
     * @var string
     */
    protected $movedTo = null;

    /**
     * The error code
     *
     * @var int
     */
    protected $error;

    /**
     * The stream for the file
     *
     * @var Psr\Http\Message\StreamInterface
     */
    protected $stream;

    /**
     * Constructs an uploaded file object with data from the $_FILES variable
     *
     * @param array $data
     *   Data compatible with an element from the $_FILES variable
     */
    public function __construct(array $data)
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Retrieve a stream representing the uploaded file.
     *
     * @return File
     *   Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     *     created.
     */
    public function getStream()
    {
        if (!$this->stream) {
            $filename = $this->tmp_name;
            if ($this->movedTo) {
                $filename = $this->movedTo;
            }
            $this->stream = new File($filename, 'r');
        }

        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * @param string $targetPath
     *   Path to which to move the uploaded file.
     *
     * @throws \InvalidArgumentException if the $targetPath specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($targetPath)
    {
        if (!move_uploaded_file($this->tmp_name, $targetPath)) {
            copy($this->tmp_name, $targetPath);
        } else {
            $this->movedTo = $targetPath;
        }
    }

    /**
     * Retrieve the file size.
     *
     * @return int|null
     *   The file size in bytes or null if unknown.
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     *
     * @return int
     *   One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Retrieve the filename sent by the client.
     *
     * @return string|null
     *   The filename sent by the client or null if none was provided.
     */
    public function getClientFilename()
    {
        return $this->name;
    }

    /**
     * Retrieve the media type sent by the client.
     *
     * @return string|null
     *   The media type sent by the client or null if none was provided.
     */
    public function getClientMediaType()
    {
        return $this->type;
    }
}
