<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr7HttpMessage\ServerRequest;
use Psr7HttpMessage\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;
use Psr7HttpMessage\Stream;

class ServerRequestTest extends TestCase
{
    /**
     * @var ServerRequest
     */
    private $serverRequest;

    /**
     * @var UploadedFileInterface
     */
    private $uploadedFile;

    public function setUp() {
        parent::setUp();

        $this->uploadedFile = new UploadedFile(new Stream('php://input'), 10, UPLOAD_ERR_OK);

        $this->serverRequest = (new ServerRequest(
            '/?foo=bar',
            'post',
            'php://input',
            ['header1' => 'foo'],
            ['cookie1' => 'value1'], // cookies
            ['qParam1' => 'qValue1'], // queryParams
            ['sp1' => 'value'], // serverParams
            [$this->uploadedFile], // uploadedFiles
            ['data'] // parsedBody
        ))->withAttribute('foo', 'bar');
    }

    public function testGetAttributes() {
        $this->assertTrue(is_array($this->serverRequest->getAttributes()));
    }

    public function testGetAttribute() {
        $this->assertEquals('bar', $this->serverRequest->getAttribute('foo'));
        $this->assertEquals('default', $this->serverRequest->getAttribute('unknown', 'default'));
        $this->assertEquals(11, $this->serverRequest->getAttribute('unknown', 11));
    }

    public function testGetCookieParams() {
        $this->assertTrue(is_array($this->serverRequest->getCookieParams()));
    }

    public function testWithCookieParams() {
        $req = $this->serverRequest->withCookieParams(['cookie2' => 'value2']);

        $this->assertNotSame($req, $this->serverRequest);
        $this->assertEquals(['cookie2' => 'value2'], $req->getCookieParams());
    }

    public function testGetQueryParams() {
        $this->assertTrue(is_array($this->serverRequest->getQueryParams()));
    }

    public function testWithQueryParams() {
        $req = $this->serverRequest->withQueryParams(['qParam2' => 'qValue2']);

        $this->assertNotSame($req, $this->serverRequest);
        $this->assertEquals(['qParam2' => 'qValue2'], $req->getQueryParams());
    }

    public function testWithAttribute() {
        $req = $this->serverRequest->withAttribute('attr1', 'val1');

        $this->assertNotSame($req, $this->serverRequest);
        $this->assertEquals('val1', $req->getAttribute('attr1'));
    }

    public function testWithoutAttribute() {
        $this->assertEquals('bar', $this->serverRequest->getAttribute('foo'));

        $req = $this->serverRequest->withoutAttribute('foo');

        $this->assertNotSame($req, $this->serverRequest);
        $this->assertEquals('default', $req->getAttribute('foo', 'default'));
    }

    public function testGetUploadedFiles() {
        $this->assertEquals([$this->uploadedFile], $this->serverRequest->getUploadedFiles());
    }

    public function uploadedFilesInvalidTreeProvider() {
        return [
            [[1]],
            [[false]],
            [[new stdClass()]],
            [[new UploadedFile(new Stream('php://stdin'), 20, UPLOAD_ERR_OK), [null]]]
        ];
    }

    /**
     * @param $argument array
     * @dataProvider uploadedFilesInvalidTreeProvider
     */
    public function testWithUploadedFilesWithInvalidFilesTree($argument) {
        $this->expectException(\InvalidArgumentException::class);

        $this->serverRequest->withUploadedFiles($argument);
    }

    public function testWithUploadedFiles() {
        $uploadedFile = new UploadedFile(new Stream('php://memory'), 10, UPLOAD_ERR_OK);
        $anotherUploadedFile = new UploadedFile(new Stream('php://stdin'), 10, UPLOAD_ERR_OK);

        $tree = [$uploadedFile, [$anotherUploadedFile]];

        $anotherServerRequest = $this->serverRequest->withUploadedFiles($tree);

        $this->assertNotSame($this->serverRequest, $anotherServerRequest);

        $this->assertEquals([$uploadedFile, [$anotherUploadedFile]], $anotherServerRequest->getUploadedFiles());
    }

    public function testGetParsedBody() {
        $this->assertEquals(['data'], $this->serverRequest->getParsedBody());
    }

    public function withParsedBodyWithInvalidArgumentProvider() {
        return [
            ['1'],
            [-1],
            ['foo'],
            [1.1],
            [true],
        ];
    }

    public function withParsedBodyWithValidArgumentProvider() {
        return [
            [null],
            [[]],
            [new stdClass],
        ];
    }

    /**
     * @param mixed $argument
     * @dataProvider withParsedBodyWithInvalidArgumentProvider
     */
    public function testWithParsedBodyWithInvalidArgument($argument) {
        $this->expectException(\InvalidArgumentException::class);

        $this->serverRequest->withParsedBody($argument);
    }

    /**
     * @param mixed $argument
     * @dataProvider withParsedBodyWithValidArgumentProvider
     */
    public function testWithParsedBody($argument) {
        $req = $this->serverRequest->withParsedBody($argument);

        $this->assertNotSame($req, $this->serverRequest);
        $this->assertEquals($argument, $req->getParsedBody());
    }

    public function testGetServerParams() {
        $this->assertEquals(['sp1' => 'value'], $this->serverRequest->getServerParams());
    }
}