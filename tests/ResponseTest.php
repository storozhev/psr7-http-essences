<?php

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr7HttpMessage\Response;

class ResponseTest extends TestCase
{

    public function testConstructorWithInvalidHeaderValue() {
        $response = new Response('php://memory', 200, '', ['foo' => ' bar', 'hello' => '\t   world  ']);
        $this->assertEquals(
            ['foo' => ['bar'], 'hello' => ['world']],
            $response->getHeaders()
        );
    }

    public function constructorWithHeadersProvider() {
        return [
            [
                ['Host' => 'example.com', 'Foo' => 'bar', 'foo' => 'baz'], // original
                ['Host' => ['example.com'], 'foo' => ['bar', 'baz']], // expected from getHeaders method
            ],
        ];
    }

    /**
     * @param $headers array
     * @param $expected array
     * @dataProvider constructorWithHeadersProvider
     */
    public function testConstructorWithHeaders($headers, $expected) {
        $response = new Response('php://memory', 200, '', $headers);

        $this->assertEquals($expected, $response->getHeaders());
    }

    public function invalidStatusCodeProvider() {
        return [
            [1], [0], [600],
        ];
    }

    /**
     * @param $statusCode int
     * @dataProvider invalidStatusCodeProvider
     */
    public function testConstructorWithStatusCode($statusCode) {
        $this->expectException(\InvalidArgumentException::class);

        new Response('php://memory', $statusCode);
    }

    public function constructorWithIncorrectBodyProvider() {
        return [
            [false],
            [null],
            [''], // empty body cannot be opened as a source
            [1],
            [[]],
            [new stdClass()],
        ];
    }

    /**
     * @param $argument mixed
     * @dataProvider constructorWithIncorrectBodyProvider
     */
    public function testConstructorWithIncorrectBody($argument) {
        $this->expectException(\InvalidArgumentException::class);

        new Response($argument);
    }

    public function testConstructorWithCorrectArguments() {
        $response = new Response(
            'php://memory',
            200,
            '',
            ['Host' => 'example.com'],
            '1.0'
        );

        $this->assertInstanceOf(StreamInterface::class, $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getReasonPhrase());
        $this->assertEquals(['Host' => ['example.com']], $response->getHeaders());
        $this->assertEquals('1.0', $response->getProtocolVersion());
    }

    public function testGetStatusCode() {
        $response = new Response('php://memory', 404);

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @param $statusCode int
     * @dataProvider invalidStatusCodeProvider
     */
    public function testWithStatusWithInvalidStatusCode($statusCode) {
        $this->expectException(\InvalidArgumentException::class);

        (new Response('php://memory'))->withStatus($statusCode);
    }

    public function withStatusProvider() {
        return [
            // statusCode | reasonPhrase | expected
            [200, '', 'OK'],
            [200, 'Okay', 'Okay'],
            [110, '', ''], // with a fake status code
            [110, 'foo', 'foo'], // with a fake status code
        ];
    }

    /**
     * @param $statusCode int
     * @param $reasonPhrase string
     * @param $expected string
     * @dataProvider withStatusProvider
     */
    public function testWithStatus(int $statusCode, string $reasonPhrase, string $expected) {
        $response = new Response('php://memory');

        $anotherResponse = $response->withStatus($statusCode, $reasonPhrase);

        $this->assertNotSame($response, $anotherResponse);

        $this->assertEquals($expected, $anotherResponse->getReasonPhrase());
    }

    public function testGetReasonPhrase() {
        $response = new Response('php://memory');

        $this->assertEquals('OK', $response->getReasonPhrase());
    }
}