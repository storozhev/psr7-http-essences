<?php

namespace Psr7HttpMessage;

use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @class UploadedFile
 * @implements UploadedFileInterface
 */
class UploadedFile implements UploadedFileInterface
{
    use AssertionTrait;

    /**
     * @var int
     */
    private $error;

    /**
     * @var string|null
     */
    private $file;

    /**
     * @var int|null
     */
    private $size;

    /**
     * @var StreamInterface|null
     */
    private $stream;

    /**
     * @var boolean
     */
    private $isMoved = false;

    /**
     * @var string|null
     */
    private $clientFilename;

    /**
     * @var string|null
     */
    private $clientMediaType;

    /**
     * @const array
     * @see http://php.net/manual/en/features.file-upload.errors.php
     */
    const UPLOAD_ERR_LIST = [
        UPLOAD_ERR_OK           => 'There is no error, the file uploaded with success',
        UPLOAD_ERR_INI_SIZE     => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE    => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        UPLOAD_ERR_PARTIAL      => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE      => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR   => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE   => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION    => 'A PHP extension stopped the file upload.',
    ];


    /**
     * @param $fileOrResourceOrStream StreamInterface|resource|string
     * @param $size int
     * @param $errorStatus int
     * @param $clientFilename string|null
     * @param $clientMediaType string|null
     * @throws \InvalidArgumentException
     */
    public function __construct($fileOrResourceOrStream, int $size, int $errorStatus, string $clientFilename = null, string $clientMediaType = null) {
        $this->assertTypeInList($fileOrResourceOrStream, [StreamInterface::class, 'resource', 'string']);

        $errCodes = array_keys(static::UPLOAD_ERR_LIST);
        $this->assertInRange($errorStatus, $errCodes[0], $errCodes[count($errCodes)-1]);

        $this->size = $size;
        $this->error = $errorStatus;
        $this->clientFilename = $clientFilename;
        $this->clientMediaType = $clientMediaType;
        if (! $this->hasError()) {
            $this->applySource($fileOrResourceOrStream);
        }
    }


    /**
     * @param $fileOrResourceOrStream StreamInterface|resource|string
     */
    private function applySource($fileOrResourceOrStream): void {
        if ($fileOrResourceOrStream instanceof StreamInterface) {
            $this->stream = $fileOrResourceOrStream;
            return;
        }

        if (is_resource($fileOrResourceOrStream)) {
            $this->stream = new Stream($fileOrResourceOrStream);
            return;
        }

        $this->file = $fileOrResourceOrStream;
    }

    /**
     * @return boolean
     */
    private function hasError(): bool {
        return UPLOAD_ERR_OK !== $this->error;
    }

    /**
     * @return string|null The filename sent by the client or null if none
     *     was provided.
     */
    public function getClientFilename(): ?string {
        return $this->clientFilename;
    }

    /**
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize(): ?int {
        return $this->size;
    }

    /**
     * @return string|null The media type sent by the client or null if none
     *     was provided.
     */
    public function getClientMediaType(): ?string {
        return $this->clientMediaType;
    }

    /**
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError(): int {
        return $this->error;
    }

    private function assertHasNotErrorAndNotMoved() {
        if ($this->hasError()) {
            throw new \RuntimeException(
                sprintf("%s couldn't return the result! Error: %s", __METHOD__, static::UPLOAD_ERR_LIST[$this->error])
            );
        }

        if (true === $this->isMoved) {
            throw new \RuntimeException('The stream has already been moved!');
        }
    }

    /**
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be
     *     created.
     */
    public function getStream(): StreamInterface {
        $this->assertHasNotErrorAndNotMoved();

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $this->stream = new Stream($this->file);
        return $this->stream;
    }

    /**
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $targetPath specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on
     *     the second or subsequent call to the method.
     */
    public function moveTo($targetPath): void {
        $this->assertTypeInList($targetPath, ['string']);

        $this->assertHasNotErrorAndNotMoved();

        if (empty(trim($targetPath))) {
            throw new \InvalidArgumentException(
                'the argument must not be an empty string!'
            );
        }

        if (null !== $this->stream) {
            $from = $this->getStream();
            $to = new Stream($targetPath, 'wb');

            $from->rewind();

            while (!$from->eof()) {
                $to->write($from->read(4096));
            }
            $this->isMoved = true;
        }

        if (null !== $this->file) {
            $this->isMoved = 'cli' === PHP_SAPI ? rename($this->file, $targetPath) : move_uploaded_file($this->file, $targetPath);
        }

        if (false === $this->isMoved) {
            throw new \RuntimeException('Couldn\'t move to ' . $targetPath);
        }
    }
}