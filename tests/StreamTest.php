<?php

declare(strict_types=1);


use PHPUnit\Framework\TestCase;
use Psr7HttpMessage\Stream;

class StreamTest extends TestCase
{
    /**
     * @var string
     */
    private $tempFile;

    /**
     * @var string
     */
    private $tempFilePrefix = "streamtest";

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        parent::tearDown();

        if (!is_null($this->tempFile) && is_file($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testReadIsNotReadable() {
        $this->expectException(\RuntimeException::class);

        $stream = new Stream("php://stdout", 'wb');
        $stream->read(1);
    }

    public function testReadSuccess() {
        $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        $resource = fopen($this->tempFile, 'w+');
        $stream = new Stream($resource);

        $data = "test";

        fwrite($resource, $data);
        fseek($resource, 0);

        $this->assertEquals($data, $stream->read(strlen($data)));
    }


    public function testClose() {
        $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        $resource = fopen($this->tempFile, 'r');
        $stream = new Stream($resource);

        $stream->close();

        $this->assertFalse(is_resource($resource));
    }

    public function testDetach() {
        $resource = fopen("php://stdin", 'r');

        $stream = new Stream($resource);

        $this->assertSame($resource, $stream->detach());
        $this->assertSame(null, $stream->detach());
    }

    public function testEof() {
        $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        file_put_contents($this->tempFile, "foo bar war");

        $resource = fopen($this->tempFile, 'r');
        $stream = new Stream($resource);

        $this->assertFalse($stream->eof());

        while (!feof($resource)) {
            fread($resource, 512);
        }

        $this->assertTrue($stream->eof());
        $stream->detach();
        $this->assertTrue($stream->eof());
    }


    public function isReadableProvider(): array {
        return [
            // mode expected fileMustNotExist
            ['r', true, false],
            ['r+', true, false],
            ['w', false, false],
            ['w+', true, false],
            ['a', false, false],
            ['a+', true, false],
            ['x', false, true],
            ['x+', true, true],
            ['c', false, false],
            ['c+', true, false],
            ['rb', true, false],
            ['r+b', true, false],
            ['rw', true, false],
            ['wb', false, false],
            ['w+b', true, false],
            ['ab', false, false],
            ['a+b', true, false],
            ['xb', false, true],
            ['x+b', true, true],
            ['cb', false, false],
            ['c+b', true, false],
        ];
    }

    public function testIsReadableWithoutResource() {
        $resource = fopen("php://stdin", 'r');

        $stream = new Stream($resource);
        $this->assertTrue($stream->isReadable());
        $stream->close();
        $this->assertFalse($stream->isReadable());
    }

    /**
     * @param string $mode
     * @param bool $expected
     * @param bool $fileMustNotExist
     * @dataProvider isReadableProvider
     */
    public function testIsReadableWithDataProvider(string $mode, bool $expected, bool $fileMustNotExist) {
        if ($fileMustNotExist) {
            $this->tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->tempFilePrefix . "_" . time();
        } else {
            $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);
        }

        $stream = new Stream($this->tempFile, $mode);

        $this->assertSame($stream->isReadable(), $expected);
    }


    public function isWritableProvider(): array {
        return [
            // mode expected fileMustNotExist
            ['r', false, false],
            ['r+', true, false],
            ['w', true, false],
            ['w+', true, false],
            ['a', true, false],
            ['a+', true, false],
            ['x', true, true],
            ['x+', true, true],
            ['c', true, false],
            ['c+', true, false],
            ['rb', false, false],
            ['r+b', true, false],
            ['rw', true, false],
            ['wb', true, false],
            ['w+b', true, false],
            ['ab', true, false],
            ['a+b', true, false],
            ['xb', true, true],
            ['x+b', true, true],
            ['cb', true, false],
            ['c+b', true, false],
        ];
    }

    public function testIsWritableWithoutResource() {
        $resource = fopen("php://stdout", 'w');

        $stream = new Stream($resource);
        $this->assertTrue($stream->isWritable());
        $stream->close();
        $this->assertFalse($stream->isWritable());
    }

    /**
     * @param string $mode
     * @param bool $expected
     * @param bool $fileMustNotExist
     * @dataProvider isWritableProvider
     */
    public function testIsWritableWithDataProvider(string $mode, bool $expected, bool $fileMustNotExist) {
        if ($fileMustNotExist) {
            $this->tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $this->tempFilePrefix . "_" . time();
        } else {
            $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);
        }

        $stream = new Stream($this->tempFile, $mode);

        $this->assertSame($stream->isWritable(), $expected);
    }


    public function isSeekableProvider(): array {
        return [
            // source mode expected
            ['php://memory', 'rb', true],
            ['php://stdin', 'rb', false],
            ['php://stdout', 'rb', false],
            ['file', 'rb', true],
        ];
    }


    public function testIsSeekableWithoutResource() {
        $resource = fopen("php://memory", 'r+');

        $stream = new Stream($resource);
        $this->assertTrue($stream->isSeekable());
        $stream->close();
        $this->assertFalse($stream->isSeekable());
    }

    /**
     * @param string $source
     * @param string $mode
     * @param bool $expected
     * @dataProvider isSeekableProvider
     */
    public function testIsSeekableWithDataProvider(string $source, string $mode, bool $expected) {
        if ('file' === $source) {
            $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);
            $stream = new Stream($this->tempFile, $mode);
        } else {
            $stream = new Stream($source, $mode);
        }
        $this->assertSame($stream->isSeekable(), $expected);
    }

    public function testGetMetadata() {
        $resource = fopen("php://stdout", 'w');

        $stream = new Stream($resource);
        $this->assertTrue(is_array($stream->getMetadata()));
        $this->assertNull($stream->getMetadata('foo_bar'));
        $stream->close();
        $this->assertNull($stream->getMetadata());
    }

    public function testTellWithoutResource() {
        $this->expectException(\RuntimeException::class);

        $stream = new Stream("php://stdout", 'w');
        $stream->detach();
        $stream->tell();
    }

    public function testTell() {
        $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        file_put_contents($this->tempFile, "foo bar war");

        $resource = fopen($this->tempFile, 'r');
        fread($resource, 7);

        $stream = new Stream($resource);
        $this->assertEquals(7, $stream->tell());
    }

    public function testGetSizeWithoutResource() {
        $stream = new Stream("php://stdout", 'w');

        $stream->detach();

        $this->assertNull($stream->getSize());
    }

    public function testGetSize() {
        $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        file_put_contents($this->tempFile, "foo bar war");

        $resource = fopen($this->tempFile, 'r');

        $stat = fstat($resource);

        $stream = new Stream($resource);

        $this->assertEquals($stat['size'], $stream->getSize());
    }

    public function testGetContentsWithNotReadableResource() {
        $this->expectException(\RuntimeException::class);

        $resource = fopen("php://stdout", 'w');
        $stream = new Stream($resource);
        $stream->getContents();
    }

    public function testGetContents() {
        $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        $content = "foo bar war";

        file_put_contents($this->tempFile, $content);

        $resource = fopen($this->tempFile, 'r');
        $stream = new Stream($resource);

        $this->assertEquals($content, $stream->getContents());
    }


    public function test_toStringWithNotReadableResource() {
        $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        $resource = fopen($this->tempFile, 'w');
        fwrite($resource, "foo");

        $stream = new Stream($resource);

        $this->assertEmpty((string) $stream);
    }

    public function test_toString() {
        $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        $content = "foo bar war";
        file_put_contents($this->tempFile, $content);

        $resource = fopen($this->tempFile, 'r');

        $stream = new Stream($resource);

        $this->assertEquals($content, (string) $stream);
    }

    public function testSeekWithNotSeekable() {
        $this->expectException(\RuntimeException::class);

        $resource = fopen("php://stdout", "rb");
        $stream = new Stream($resource);
        $stream->seek(0);
    }

    public function testSeek() {
        $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);
        $resource = fopen($this->tempFile, 'w+');

        fwrite($resource, "foo bar");

        $stream = new Stream($resource);
        $stream->seek(1);

        $this->assertEquals(1, ftell($resource));

        return $stream;
    }

    /**
     * @param Stream $stream
     * @depends testSeek
     */
    public function testRewind(Stream $stream) {
        $stream->rewind();

        $resource = $stream->detach();

        $this->assertEquals(0, ftell($resource));
    }

    public function testAttachWithInvalidArgument() {
        $this->expectException(\InvalidArgumentException::class);

        $stream = new Stream('php://stdout');
        $stream->close();
        $stream->attach(true, 'r');
        $stream->attach(1, 'r');
        $stream->attach(null, 'r');
    }

    public function testAttachWithNotStreamResource() {
        $this->expectException(\InvalidArgumentException::class);

        $stream = new Stream('php://stdout');
        $stream->close();

        $resource = curl_init();
        $stream->attach($resource, 'r');
    }

    public function testAttachWithFakeResource() {
        $this->expectException(\InvalidArgumentException::class);

        $stream = new Stream('php://stdout');
        $stream->close();

        $stream->attach('fake', 'r');
    }

    public function testAttachWithLink() {
        $stream = new Stream('php://stdout');
        $stream->close();

        $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);
        $stream->attach($this->tempFile, 'r');

        $this->assertTrue($stream->isReadable());
    }


    public function testWriteWithNotWritable() {
        $this->expectException(\RuntimeException::class);

        $stream = new Stream("php://stdin");
        $stream->write('foo');
    }

    public function testWrite() {
        $this->tempFile = tempnam(sys_get_temp_dir(), $this->tempFilePrefix);

        $content = "foo bar";

        $resource = fopen($this->tempFile, 'w+');
        $stream = new Stream($resource);

        $stream->write($content);
        fseek($resource, 0);

        $this->assertEquals($content, fread($resource, strlen($content)));
    }
}