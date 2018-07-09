<?php

use PHPUnit\Framework\TestCase;
use Psr7HttpMessage\Request;
use Psr\Http\Message\UriInterface;
use Psr7HttpMessage\Uri;

class RequestTest extends TestCase
{

    /**
     * @var Request
     */
    private $request;

    public function setUp() {
        parent::setUp();

        $this->request = new Request('?foo=bar', 'GET');
    }

    public function testGetMethod() {
        $this->assertEquals('GET', $this->request->getMethod());
    }


    public function withMethodWithInvalidArgumentProvider() {
        return [
            [false],
            [1],
            [[]],
            [new stdClass()],
        ];
    }

    /**
     * @param $argument mixed
     * @dataProvider withMethodWithInvalidArgumentProvider
     */
    public function testWithMethodWithInvalidArgument($argument) {
        $this->expectException(\InvalidArgumentException::class);

        $this->request->withMethod($argument);
    }

    public function testWithMethod() {
        $anotherRequest = $this->request->withMethod('post');
        $this->assertNotSame($anotherRequest, $this->request);

        $this->assertEquals('post', $anotherRequest->getMethod());
    }

    public function testGetUri() {
        $this->assertInstanceOf(UriInterface::class, $this->request->getUri());

        $this->assertEquals('?foo=bar', (string) $this->request->getUri());
    }


    public function withUriProvider() {
        return [
            // header | uri  | preservedHost | expected
            ['', '', false, ''],
            [false, 'http://bar.foo', false, 'bar.foo'],
            ['foo.bar', 'http://bar.foo:81', false, 'bar.foo:81'],
            ['foo.bar', '?bar=foo', false, 'foo.bar'],
            ['', '', true, ''],
            [false, 'http://bar.foo', true, 'bar.foo'],
            ['foo.bar', 'http://bar.foo:81', true, 'foo.bar'],
            ['foo.bar', '?bar=foo', true, 'foo.bar'],
        ];
    }

    /**
     * @param $hostHeader mixed
     * @param $uri string
     * @param $preserveHost boolean
     * @param $expected mixed
     * @dataProvider withUriProvider
     */
    public function testWithUri($hostHeader, string $uri, $preserveHost, $expected) {
        $request = new Request('', 'GET');

        if (false !== $hostHeader) {
            $request = $request->withHeader('Host', $hostHeader);
        }

        $anotherRequest = $request->withUri(new Uri($uri), $preserveHost);

        $this->assertNotSame($request, $anotherRequest);

        $this->assertEquals([$expected], $anotherRequest->getHeader('Host'));
    }

    public function testWithRequestTargetWithInvalidArgument() {
        $this->expectException(\InvalidArgumentException::class);

        $this->request->withRequestTarget('http://www.example.org/where? q=now');
    }

    public function testWithRequestTarget() {
        $request = $this->request->withRequestTarget('?foo=bar');

        $this->assertNotSame($request, $this->request);

        $this->assertEquals('?foo=bar', $request->getRequestTarget());
    }

    public function testGetRequestTargetWithProvidedRequestTarget() {
        $request = (new Request('', 'GET'))->withRequestTarget('/?foo=bar');

        $this->assertEquals('/?foo=bar', $request->getRequestTarget());
    }

    public function testGetRequestTargetWithProvidedUri() {
        $request = new Request('http://example.com/path?bar=baz', 'GET');

        $this->assertEquals('/path?bar=baz', $request->getRequestTarget());
    }

    public function testGetRequestTargetWithNoProvided() {
        $request = new Request('', 'GET');

        $this->assertEquals('/', $request->getRequestTarget());
    }

}
