<?php

use \PHPUnit\Framework\TestCase;
use Psr7HttpMessage\Uri;

class UriTest extends TestCase
{

    /**
     * @var Uri
     */
    private $uri;

    public function setUp() {
        parent::setUp();

        $this->uri = new Uri('https://user:password@DOMAIN.zone/path/to/?a=1&b=2#fragment');
    }

    public function testGetScheme() {
        $this->assertEquals('https', $this->uri->getScheme());
    }


    public function withSchemeWithInvalidArgumentProvider() {
        return[
            [1],
            [1.1],
            [true],
            [[]],
            [new stdClass],
        ];
    }

    /**
     * @param mixed $argument
     * @dataProvider withSchemeWithInvalidArgumentProvider
     */
    public function testWithSchemeWithInvalidArgument($argument) {
        $this->expectException(\InvalidArgumentException::class);

        $this->uri->withScheme($argument);
    }

    public function testWithScheme() {
        $newUri = $this->uri->withScheme('HTTP');

        $this->assertNotSame($newUri, $this->uri);
        $this->assertEquals('http', $newUri->getScheme());

    }

    public function testGetPath() {
        $this->assertEquals('/path/to/', $this->uri->getPath());
    }


    public function withPathWithInvalidArgumentProvider() {
        return [
            [false],
            [0],
            [1],
            [[]],
            [new \stdClass()],
        ];
    }

    /**
     * @param $argument mixed
     * @dataProvider withPathWithInvalidArgumentProvider
     */
    public function testWithPathWithInvalidArgument($argument) {
        $this->expectException(\InvalidArgumentException::class);

        $this->uri->withPath($argument);
    }

    public function testWithPath() {
        $uri = $this->uri->withPath('/path/to/');

        $this->assertSame($uri, $this->uri);

        $anotherUri = $this->uri->withPath('/foo/bar/');
        $this->assertNotSame($anotherUri, $this->uri);

        $this->assertEquals('/foo/bar/', $anotherUri->getPath());
        $this->assertEquals('https://user:password@domain.zone/foo/bar/?a=1&b=2#fragment', (string) $anotherUri);
    }

    public function testWithPathEncodeSuccess() {
        $uri = $this->uri->withPath('/益#path?/t%6F/');

        $this->assertEquals('/%E7%9B%8A%23path%3F/t%6F/', $uri->getPath());
        $this->assertEquals('https://user:password@domain.zone/%E7%9B%8A%23path%3F/t%6F/?a=1&b=2#fragment', (string) $uri);
    }

    public function testGetHost() {
        $this->assertEquals('domain.zone', $this->uri->getHost());
    }


    public function withHostWithInvalidArgumentProvider() {
        return [
            [1],
            [false],
            [[]],
        ];
    }

    /**
     * @param mixed $argument
     * @dataProvider withHostWithInvalidArgumentProvider
     */
    public function testWithHostWithInvalidArgument($argument) {
        $this->expectException(\InvalidArgumentException::class);

        $this->uri->withHost($argument);
    }

    public function testWithHost() {
        $uri = $this->uri->withHost('test.TE');

        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals('test.te', $uri->getHost());

        $uriCopy = $uri->withHost('TEST.te');
        $this->assertSame($uri, $uriCopy);
    }

    public function getPortProvider() {
        return [
            ['test.te', null],
            ['http://test.te', null],
            ['http://test.te:8080', 8080],
            ['http://test.te:80', null],
        ];
    }

    /**
     * @param string $url
     * @param null|int $port
     * @dataProvider getPortProvider
     */
    public function testGetPort(string $url, ?int $port) {
        $uri = new Uri($url);

        $this->assertEquals($port, $uri->getPort());
    }


    public function withPortWithInvalidArgumentProvider() {
        return [
            ['foo'],
            [true],
            [0],
            [-1],
            [65536],
            [[]],
        ];
    }


    /**
     * @param mixed $argument
     * @dataProvider withPortWithInvalidArgumentProvider
     */
    public function testWithPortWithInvalidArgument($argument) {
        $this->expectException(\InvalidArgumentException::class);

        $this->uri->withPort($argument);
    }

    public function testWithPort() {
        $uri = $this->uri->withPort(8080);

        $this->assertNotSame($uri, $this->uri);
        $this->assertEquals(8080, $uri->getPort());

        $uriCopy = $uri->withPort(8080);
        $this->assertSame($uri, $uriCopy);
    }


    public function testGetFragment() {
        $this->assertEquals('fragment', $this->uri->getFragment());
    }

    public function withFragmentWithInvalidArgumentProvider() {
        return [
            [false],
            [1],
            [-1],
            [[]],
            [new stdClass()]
        ];
    }

    /**
     * @param $argument mixed
     * @dataProvider withFragmentWithInvalidArgumentProvider
     */
    public function testWithFragmentWithInvalidArgument($argument) {
        $this->expectException(\InvalidArgumentException::class);

        $this->uri->withFragment($argument);
    }

    public function testWithFragment() {
        $uri = $this->uri->withFragment('fragment');
        $this->assertSame($uri, $this->uri);

        $anotherUri = $this->uri->withFragment('foo');
        $this->assertNotSame($this->uri, $anotherUri);

        $this->assertEquals('foo', $anotherUri->getFragment());

        $this->assertEquals('https://user:password@domain.zone/path/to/?a=1&b=2#foo', (string) $anotherUri);
    }

    public function testWithFragmentEncodeSuccess() {
        // '/' and '?' must not be encoded
        $uri = $this->uri->withFragment('#益?/f%6Fo');

        $this->assertEquals('%23%E7%9B%8A?/f%6Fo', $uri->getFragment());
        $this->assertEquals('https://user:password@domain.zone/path/to/?a=1&b=2#%23%E7%9B%8A?/f%6Fo', (string) $uri);
    }


    public function withQueryWithInvalidArgumentProvider() {
        return [
            [false],
            [1],
            [-1],
            [[]],
            [new stdClass()]
        ];
    }

    /**
     * @param $argument mixed
     * @dataProvider withQueryWithInvalidArgumentProvider
     */
    public function testWithQueryWithInvalidArgument($argument) {
        $this->expectException(\InvalidArgumentException::class);

        $this->uri->withQuery($argument);
    }

    public function testWithQuery() {
        $uri = $this->uri->withQuery('a=1&b=2');
        $this->assertSame($uri, $this->uri);

        $anotherUri = $this->uri->withQuery('foo=bar');
        $this->assertNotSame($this->uri, $anotherUri);

        $this->assertEquals('foo=bar', $anotherUri->getQuery());
        $this->assertEquals('https://user:password@domain.zone/path/to/?foo=bar#fragment', (string) $anotherUri);
    }

    public function testWithQueryEncodeSuccess() {
        // '/' and '?' must not be encoded
        $uri = $this->uri->withQuery('?#益=foo/&var=ba%7A');
        $this->assertSame('?%23%E7%9B%8A=foo/&var=ba%7A', $uri->getQuery());
        $this->assertSame('https://user:password@domain.zone/path/to/??%23%E7%9B%8A=foo/&var=ba%7A#fragment', (string) $uri);
    }

    public function testGetUserInfo() {
        $this->assertEquals('user:password', $this->uri->getUserInfo());
    }


    public function testWithUserInfo() {
        $uri = $this->uri->withUserInfo('user', 'password');

        $this->assertSame($uri, $this->uri);

        $anotherUri = $this->uri->withUserInfo('foo', 'bar');
        $this->assertNotSame($this->uri, $anotherUri);

        $this->assertEquals('foo:bar', $anotherUri->getUserInfo());
    }


    public function getAuthorityProvider() {
        return [
            ['', ''],
            ['?foo=bar', ''],
            ['http://foo.bar', 'foo.bar'],
            ['http://user@foo.bar', 'user@foo.bar'],
            ['http://user:pass@foo.bar', 'user:pass@foo.bar'],
            ['http://user:pass@foo.bar:80', 'user:pass@foo.bar'],
            ['http://user:pass@foo.bar:81', 'user:pass@foo.bar:81'],
        ];
    }

    /**
     * @param $uriStr string
     * @param $expected string
     * @dataProvider getAuthorityProvider
     */
    public function testGetAuthority(string $uriStr, string $expected) {
        $uri = new Uri($uriStr);
        $this->assertEquals($expected, $uri->getAuthority());
    }

    public function test__toString() {
        $this->assertEquals('https://user:password@domain.zone/path/to/?a=1&b=2#fragment', (string) $this->uri);

        $uri2 = $this->uri->withFragment('bar');
        $this->assertEquals('https://user:password@domain.zone/path/to/?a=1&b=2#bar', (string) $uri2);

        $uri3 = $uri2->withPath('/foo/');
        $this->assertEquals('https://user:password@domain.zone/foo/?a=1&b=2#bar', (string) $uri3);
    }

}