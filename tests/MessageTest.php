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

        $this->assertEquals($message->hasHeader('test'), true);
        $this->assertEquals($message->hasHeader('tEsT'), true);
        $this->assertEquals($message->hasHeader('test1'), false);
    }

    public function testGetHeader() {
        $message = $this->message
            ->withHeader('a', 'aaa')
            ->withHeader('b', ['bb', 'bbb']);

        $this->assertEquals($message->getHeader('a'), ['aaa']);
        $this->assertEquals($message->getHeader('b'), ['bb', 'bbb']);
        $this->assertEquals($message->getHeader('c'), []);
    }

    public function testGetHeaders() {
        $message = $this->message
            ->withHeader('a', 'aaa')
            ->withHeader('b', ['b', 'bb']);

        $this->assertEquals($message->getHeaders(), [
           'a' => ['aaa'],
           'b' => ['b', 'bb'],
        ]);
    }

    public function testGetHeaderLine() {
        $message = $this->message
            ->withHeader('a', [])
            ->withHeader('b', ['bb', 'bbb']);

        $this->assertEquals($message->getHeaderLine('a'), '');
        $this->assertEquals($message->getHeaderLine('B'), 'bb, bbb');
    }

    public function testWithHeader() {
        $message = $this->message
            ->withHeader('a', 'aaa')
            ->withHeader('b', 'bb')
            ->withHeader('B', 'bbb');

        $this->assertEquals($message->getHeader('A'), ['aaa']);
        $this->assertEquals($message->getHeader('b'), ['bbb']);
        $this->assertEquals($message->getHeaders(), [
            'a' => ['aaa'],
            'B' => ['bbb'],
        ]);
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

        $this->assertEquals($message->getHeader('A'), ['aaa']);
        $this->assertEquals($message->getHeader('b'), ['bb', 'bbb']);
        $this->assertEquals($message->getHeaders(), [
            'a' => ['aaa'],
            'B' => ['bb', 'bbb'],
        ]);
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

        $this->assertEquals($message->getProtocolVersion(), "1.0");
    }

    public function testWithoutHeader() {
        $message = $this->message
            ->withHeader('a', 'aaa')
            ->withoutHeader('a');

        $this->assertEquals($message->getHeaders(), []);
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