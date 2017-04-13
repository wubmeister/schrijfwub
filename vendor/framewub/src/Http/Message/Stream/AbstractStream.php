<?php

/**
 * Value object representing a stream.
 *
 * @package    framewub/http-message
 * @author     Wubbo Bos <wubbo@wubbobos.nl>
 * @copyright  Copyright (c) Wubbo Bos
 * @license    GPL
 * @link       https://github.com/wubmeister/framewub
 */

namespace Framewub\Http\Message\Stream;

use RuntimeException;
use Psr\Http\Message\StreamInterface;

class AbstractStream implements StreamInterface
{
    /**
     * The file name
     *
     * @var string
     */
    protected $filename;

    /**
     * The file handle
     *
     * @var int
     */
    protected $file;

    /**
     * Readable flag
     *
     * @var bool
     */
    protected $readable = true;


    /**
     * Writable flag
     *
     * @var bool
     */
    protected $writable = false;

    /**
     * Constructor opens the file for reading
     */
    public function __construct()
    {
        if ($this->filename) {
            $this->file = fopen($this->filename, 'r');
        }
    }

    /**
     * Converts the entire contents of a stream to a string
     *
     * @return string
     */
    public function __toString()
    {
        $this->rewind();
        return $this->getContents();
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        fclose($this->file);
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null
     *   Underlying PHP stream, if any
     */
    public function detach()
    {
        fclose($this->file);
        return null;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null
     *   Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        return filesize($this->filename);
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int
     *   Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        return ftell($this->file);
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return feof($this->file);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->getMetadata('seekable');
    }

    /**
     * Seek to a position in the stream.
     *
     * @param int $offset
     *   Stream offset
     * @param int $whence
     *   Specifies how the cursor position will be calculated based on the seek
     *   offset. Valid values are identical to the built-in PHP $whence values
     *   for `fseek()`.  SEEK_SET: Set position equal to offset bytes SEEK_CUR:
     *   Set position to current location plus offset SEEK_END: Set position to
     *   end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        fseek($this->file, $offset, $whence);
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        fseek($this->file, 0, SEEK_SET);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * Write data to the stream.
     *
     * @param string $string
     *   The string that is to be written.
     *
     * @return int
     *   Returns the number of bytes written to the stream.
     *
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (!$this->writable) {
            throw new RuntimeException("Stream is not writable");
        }
        return fwrite($this->file, $string);
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length
     *   Read up to $length bytes from the object and return them. Fewer than
     *   $length bytes may be returned if underlying stream call returns fewer
     *   bytes.
     *
     * @return string
     *   Returns the data read from the stream, or an empty string if no bytes
     *   are available.
     *
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (!$this->readable) {
            throw new RuntimeException("Stream is not readable");
        }

        $result = @fread($this->file, $length);
        if ($result === FALSE) {
            throw new RuntimeException("Stream is not readable");
        }
        return $result;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        $result = '';
        while ($r = @fread($this->file, 8192)) {
            $result .= $r;
        }
        if ($r === FALSE) {
            throw new RuntimeException("Stream is not readable");
        }
        return $result;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @param string $key
     *   Specific metadata to retrieve.
     *
     * @return array|mixed|null
     *   Returns an associative array if no key is provided. Returns a specific
     *   key value if a key is provided and the value is found, or null if the
     *   key is not found.
     */
    public function getMetadata($key = null)
    {
        $metadata = stream_get_meta_data($this->file);
        if ($key) {
            return $metadata[$key];
        }
        return $metadata;
    }
}