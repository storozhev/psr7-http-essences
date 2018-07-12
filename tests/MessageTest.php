<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr7HttpMessage\Message;
use Psr7HttpMessage\Stream;
use Psr\Http\Message\StreamInterface;


class MessageTest extends TestCase
{
    /**
     * @var Message
     */
    private $message;

    public function setUp(): void {
        parent::setUp();

        $this->message = new Message(new Stream('php://memory'));
    }

    public function testHasHeader() {
        $message= $this->message->withHeader('test', 'a');

        $this->assertEquals(true, $message->hasHeader('test'));
        $this->assertEquals(true, $message->hasHeader('tEsT'));
        $this->assertEquals(false, $message->hasHeader('test1'));
    }

    public function testGetHeader() {
        $message = $this->message
            ->withHeader('a', 'aaa')
            ->withHeader('b', ['bb', 'bbb']);

        $this->assertEquals(['aaa'], $message->getHeader('a'));
        $this->assertEquals(['bb', 'bbb'], $message->getHeader('b'));
        $this->assertEquals([], $message->getHeader('c'));
    }

    public function testGetHeaders() {
        $message = $this->message
            ->withHeader('a', 'aaa')
            ->withHeader('b', ['b', 'bb']);

        $this->assertEquals([
           'a' => ['aaa'],
           'b' => ['b', 'bb'],
        ], $message->getHeaders());
    }

    public function testGetHeaderLine() {
        $message = $this->message
            ->withHeader('a', [])
            ->withHeader('b', ['bb', 'bbb']);

        $this->assertEquals('', $message->getHeaderLine('a'));
        $this->assertEquals('bb, bbb', $message->getHeaderLine('B'));
    }

    public function testWithHeader() {
        $message = $this->message
            ->withHeader('a', 'aaa')
            ->withHeader('b', 'bb')
            ->withHeader('B', 'bbb');

        $this->assertEquals(['aaa'], $message->getHeader('A'));
        $this->assertEquals(['bbb'], $message->getHeader('b'));
        $this->assertEquals([
            'a' => ['aaa'],
            'B' => ['bbb'],
        ], $message->getHeaders());
    }

    public function testWithHeaderExpectIncorrectNameException() {
        $this->expectException(\InvalidArgumentException::class);

        $this->message->withHeader(1, []);
    }

    public function testWithHeaderExpectIncorrectValueException() {
        $this->expectException(\InvalidArgumentException::class);

        $this->message->withHeader("aaa", false);
    }

    public function testWithAddedHeader() {
        $message = $this->message
            ->withAddedHeader('a', 'aaa')
            ->withAddedHeader('b', 'bb')
            ->withAddedHeader('B', 'bbb');

        $this->assertEquals(['aaa'], $message->getHeader('A'));
        $this->assertEquals(['bb', 'bbb'], $message->getHeader('b'));
        $this->assertEquals([
            'a' => ['aaa'],
            'B' => ['bb', 'bbb'],
        ], $message->getHeaders());
    }

    public function testWithAddedHeaderExpectIncorrectNameException() {
        $this->expectException(\InvalidArgumentException::class);

        $this->message->withAddedHeader(1, "");
    }

    public function testWithAddedHeaderExpectIncorrectValueException() {
        $this->expectException(\InvalidArgumentException::class);

        $this->message->withAddedHeader("aaa", 1);
    }

    public function testWithProtocolVersion() {
        $message = $this->message->withProtocolVersion("1.0");

        $this->assertEquals("1.0", $message->getProtocolVersion());
    }

    public function testWithoutHeader() {
        $message = $this->message
            ->withHeader('a', 'aaa')
            ->withoutHeader('a');

        $this->assertEquals([], $message->getHeaders());
    }

    public function testGetBody() {
        $body = $this->message->getBody();

        $this->assertTrue($body instanceof StreamInterface);
    }

    public function testWithBody() {
        $newBody = new Stream("php://stdin");
        $message = $this->message->withBody($newBody);

        $this->assertNotSame($message, $this->message);
        $this->assertNotSame($newBody, $this->message->getBody());
    }
}