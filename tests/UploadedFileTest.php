<?php

use PHPUnit\Framework\TestCase;
use Psr7HttpMessage\UploadedFile;
use Psr\Http\Message\StreamInterface;
use Psr7HttpMessage\Stream;

/**
 * @class UploadedFileTest
 * @extends TestCase
 */
class UploadedFileTest extends TestCase
{
    /**
     * @var array
     */
    private $filesList;

    /**
     * @var string
     */
    private $tempFilePrefix = 'uploadedfile_test';

    protected function setUp() {
        $this->filesList = [];
    }

    protected function tearDown() {
        foreach ($this->filesList as $item) {
            if (file_exists($item)) {
                unlink($item);
            }
        }
    }

    public function invalidArgumentsProvider() {
        return [
            // $fileOrResourceOrStream | errorStatus (from 0 to 8 won't throw an exception)
            [null, 1],
            [new stdClass(), 1],
            [0, 1],
            [true, 1],
            ['', -1],
            ['', 9],
        ];
    }

    /**
     * @param $fileOrResourceOrStream mixed
     * @param $errorStatus int
     * @dataProvider invalidArgumentsProvider
     */
    public function testConstructorWithInvalidArguments($fileOrResourceOrStream, int $errorStatus) {
        $this->expectException(\InvalidArgumentException::class);

        new UploadedFile($fileOrResourceOrStream, 1, $errorStatus);
    }

    public function testGetSize() {
        $file = new UploadedFile('', 10, UPLOAD_ERR_OK);

        $this->assertEquals(10, $file->getSize());
    }


    public function testGetClientMediaType() {
        $file = new UploadedFile('', 10, UPLOAD_ERR_OK, 'file.txt', 'text/html');

        $this->assertEquals('text/html', $file->getClientMediaType());
    }

    public function testGetClientFilename() {
        $file = new UploadedFile('', 10, UPLOAD_ERR_OK, 'file.txt');

        $this->assertEquals('file.txt', $file->getClientFilename());
    }


    public function testGetError() {
        $file = new UploadedFile('', 0, UPLOAD_ERR_INI_SIZE);

        $this->assertEquals(UPLOAD_ERR_INI_SIZE, $file->getError());
    }

    public function testGetStreamWithUploadingError() {
        $this->expectException(\RuntimeException::class);

        $file = new UploadedFile('', 0, UPLOAD_ERR_INI_SIZE);
        $file->getStream();
    }

    public function testGetStreamWhenItHasBeenMoved() {
        $this->filesList[] = $tempFileFrom = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);
        $this->filesList[] = $tempFileTo = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        $stream = new Stream($tempFileFrom, 'wb+');
        $stream->write('foo');

        $uploadedFile = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK);
        $uploadedFile->moveTo($tempFileTo);

        $this->assertEquals('foo', file_get_contents($tempFileTo));

        $this->expectException(\RuntimeException::class);
        $uploadedFile->getStream();
    }

    public function testGetStreamWhenGivenResource() {
        $resource = fopen('php://memory', 'rb');
        $uf = new UploadedFile($resource, 100, UPLOAD_ERR_OK);

        $stream = $uf->getStream();
        $this->assertInstanceOf(StreamInterface::class, $stream);

        $detached = $stream->detach();
        $this->assertInternalType('resource', $detached);

        $this->assertSame($resource, $detached);
    }

    public function testGetStreamWhenGivenStreamInterface() {
        $stream = new Stream("php://input");

        $uf = new UploadedFile($stream, 100, UPLOAD_ERR_OK);

        $this->assertInstanceOf(StreamInterface::class, $stream);

        $this->assertSame($stream, $uf->getStream());
    }

    public function invalidArgumentForMoveToProvider() {
        return [
            [false],
            [null],
            [1],
            [0],
            [[]],
            [new stdClass()],
        ];
    }

    /**
     * @param $argument mixed
     * @dataProvider invalidArgumentForMoveToProvider
     */
    public function testMoveToWithInvalidArgument($argument) {
        $this->expectException(\InvalidArgumentException::class);

        $uf = new UploadedFile('', 0, UPLOAD_ERR_OK);
        $uf->moveTo($argument);
    }

    public function testMoveToWithEmptyStringAsArgument() {
        $this->expectException(\InvalidArgumentException::class);

        $uf = new UploadedFile('', 0, UPLOAD_ERR_OK);

        $uf->moveTo(' ');
    }

    public function testMoveToWithUploadingError() {
        $this->expectException(\RuntimeException::class);

        $uf = new UploadedFile('', 0, UPLOAD_ERR_CANT_WRITE);

        $uf->moveTo("to");
    }

    public function testMoveToWithItHasBeenMoved() {
        $this->filesList[] = $tempFileFrom = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);
        $this->filesList[] = $tempFileTo = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);
        $this->filesList[] = $anotherTempFileTo = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        $resourceFrom = fopen($tempFileFrom, 'wb+');
        fwrite($resourceFrom, 'foo');

        $uploadedFile = new UploadedFile($resourceFrom, 3 ,UPLOAD_ERR_OK);
        $uploadedFile->moveTo($tempFileTo);

        $this->assertEquals('foo', file_get_contents($tempFileTo));

        $this->expectException(\RuntimeException::class);

        $uploadedFile->moveTo($anotherTempFileTo);
    }

    public function testMoveToFromFile() {
        $this->filesList[] = $tempFileFrom = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);
        $this->filesList[] = $tempFileTo = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        file_put_contents($tempFileFrom, "bar");

        $uploadedFile = new UploadedFile($tempFileFrom, 3, UPLOAD_ERR_OK);
        $uploadedFile->moveTo($tempFileTo);

        $this->assertEquals('bar', file_get_contents($tempFileTo));
    }

    public function testMoveToFromStream() {
        $this->filesList[] = $tempFileTo = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        $streamFrom = new Stream('php://memory', 'wb+');
        $streamFrom->write('baz');

        $uploadFile = new UploadedFile($streamFrom, $streamFrom->getSize(), UPLOAD_ERR_OK);

        $uploadFile->moveTo($tempFileTo);

        $this->assertEquals('baz', file_get_contents($tempFileTo));
    }
}