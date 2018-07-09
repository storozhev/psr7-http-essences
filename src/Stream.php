<?php

namespace Psr7HttpMessage;


use Psr\Http\Message\StreamInterface;

/**
 * @class Stream
 * @implements StreamInterface
 */
class Stream implements StreamInterface
{
    use AssertionTrait;

    /**
     * @var resource
     */
    private $resource;

    /**
     * @const array
     */
    const WRITE_SIGNS = ['w', 'a', 'x', 'c', '+'];

    /**
     * @const array
     */
    const READ_SIGNS = ['r', '+'];

    /**
     * @param string|resource $resource
     * @param string $mode
     */
    public function __construct($resource, $mode = 'rb') {
        $this->attach($resource, $mode);
    }

    /**
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length): string {
        if (!$this->isReadable()) {
            throw new \RuntimeException("The stream is not readable");
        }

        $data = fread($this->resource, $length);

        if (false === $data) {
            throw new \RuntimeException("couldn't read the resource!");
        }

        return $data;
    }


    public function close(): void {
        if (!$this->resource) {
            return;
        }

        $res = $this->detach();
        fclose($res);
    }

    /**
     * @return resource|null
     */
    public function detach() {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    /**
     * @return bool
     */
    public function eof(): bool {
        return !$this->resource
            ? true
            : feof($this->resource);
    }

    /**
     * @return bool
     */
    public function isReadable(): bool {
        $mode = $this->getMetadata('mode');

        if (null === $mode) {
            return false;
        }

        $res = array_filter(static::READ_SIGNS, function($v) use ($mode) {
            return false !== strstr($mode, $v);
        });

        return count($res) > 0;
    }

    /**
     * @return bool
     */
    public function isWritable(): bool {
        $mode = $this->getMetadata('mode');

        if (null === $mode) {
            return false;
        }

        $res = array_filter(static::WRITE_SIGNS, function($v) use ($mode) {
            return false !== strstr($mode, $v);
        });

        return count($res) > 0;
    }

    /**
     * @return bool
     */
    public function isSeekable(): bool {
        $seekable = $this->getMetadata('seekable');

        if (null === $seekable) {
            return false;
        }

        return $seekable;
    }

    /**
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null) {
        if(!$this->resource) {
            return null;
        }

        $md = stream_get_meta_data($this->resource);

        if (null === $key) {
            return $md;
        }

        return array_key_exists($key, $md) ? $md[$key] : null;
    }


    /**
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell(): int {
        if (!$this->resource) {
            throw new \RuntimeException("There's no resource to tell the position!");
        }

        $position = ftell($this->resource);

        if (false === $position) {
            throw new \RuntimeException('cannot tell the position!');
        }
        return $position;
    }


    /**
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize(): ?int {
        if (!$this->resource) {
            return null;
        }

        $info = fstat($this->resource);

        return false !== $info
            ? $info['size']
            : null;
    }

    /**
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents(): string {
        if (!$this->isReadable()) {
            throw new \RuntimeException("The stream is not readable!");
        }

        $content = stream_get_contents($this->resource);

        if (false === $content) {
            throw new \RuntimeException('An error occurred during reading the resource!');
        }

        return $content;
    }

    /**
     * @return string
     */
    public function __toString(): string {
        if (!$this->isReadable()) {
            return "";
        }

        try {
            if ($this->isSeekable()) {
                $this->seek(0);
            }

            return $this->getContents();

        } catch (\RuntimeException $e) {
            return "";
        }
    }


    public function rewind(): void {
        $this->seek(0);
    }

    /**
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET): void {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('The stream is not seekable');
        }

        $done = fseek($this->resource, $offset, $whence);

        if (-1 === $done) {
            throw new \RuntimeException('Seeking error');
        }
    }


    /**
     * @param resource|string $resource
     * @param string $mode
     * @throws \InvalidArgumentException
     */
    public function attach($resource, string $mode) {
        $this->assertTypeInList($resource, ['string', 'resource']);

        if (is_resource($resource)) {
            if ('stream' !== get_resource_type($resource)) {
                throw new \InvalidArgumentException("the type of a resource must be 'stream'");
            }
            $this->resource = $resource;
            return;
        }

        $err = null;

        set_error_handler(function(/** @noinspection PhpUnusedParameterInspection */ $errNo, $errStr) use (&$err) {
            $err = $errStr;
        }, E_WARNING);

        $handle = fopen($resource, $mode);

        restore_error_handler();

        if (null !== $err) {
            throw new \InvalidArgumentException(sprintf("Cannot open '%s', Error: %s", $resource, $err));
        }

        $this->resource = $handle;
    }

    /**
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string): int {
        if (!$this->isWritable()) {
            throw new \RuntimeException('The stream is not writable');
        }

        $written = fwrite($this->resource, $string);

        if (false === $written) {
            throw new \RuntimeException("couldn't write the string into the stream!");
        }

        return $written;
    }


    public function __destruct() {
        $this->close();
    }
}