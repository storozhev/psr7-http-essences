<?php

namespace Psr7HttpMessage;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @class ServerRequest
 * @extends Message
 * @implements ServerRequestInterface
 */
class ServerRequest extends Request
    implements ServerRequestInterface
{
    use AssertionTrait;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var array
     */
    protected $cookieParams = [];

    /**
     * @var array
     */
    protected $serverParams = [];

    /**
     * @var array
     */
    protected $queryParams;

    /**
     * @var array
     */
    protected $uploadedFiles;

    /**
     * @var array|\object|null
     */
    protected $parsedBody;

    /**
     * @param $uri UriInterface|string
     * @param $method string
     * @param $body StreamInterface|string|resource
     * @param $headers array
     * @param $protocolVersion string
     * @param array $cookies
     * @param array $queryParams
     * @param array $serverParams
     * @param array $uploadedFiles
     * @param array|\object|null $parsedBody
     * @throws \InvalidArgumentException
     */
    public function __construct(
        $uri,
        string $method,
        $body = 'php://input',
        array $headers = [],
        array $cookies = [],
        array $queryParams = [],
        array $serverParams = [],
        array $uploadedFiles = [],
        $parsedBody = null,
        string $protocolVersion = '1.1'
    ) {
        parent::__construct($uri, $method, $body, $headers, $protocolVersion);

        $this->cookieParams = $cookies;
        $this->queryParams = $queryParams;
        $this->serverParams = $serverParams;
        $this->assertValidUploadedFilesTree($uploadedFiles);
        $this->uploadedFiles = $uploadedFiles;
        $this->parsedBody = $parsedBody;
    }

    /**
     * @return array
     */
    public function getAttributes(): array  {
        return $this->attributes;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($name, $default = null)  {
        return $this->hasAttribute($name) ? $this->attributes[$name] : $default;
    }

    /**
     * @param string $name
     * @return bool
     */
    private function hasAttribute(string $name): bool {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * @return array
     */
    public function getCookieParams(): array {
        return $this->cookieParams;
    }

    /**
     * @param array $cookies Array of key/value pairs representing cookies.
     * @return static
     */
    public function withCookieParams(array $cookies): self {
        $instance = clone $this;

        $instance->cookieParams = $cookies;

        return $instance;
    }

    /**
     * @return array
     */
    public function getQueryParams(): array {
        return $this->queryParams;
    }

    /**
     * @param array $query Array of query string arguments, typically from
     *     $_GET.
     * @return static
     */
    public function withQueryParams(array $query): self {
        $instance = clone $this;

        $instance->queryParams = $query;

        return $instance;
    }


    /**
     * @param string $name The attribute name.
     * @param mixed $value The value of the attribute.
     * @return static
     */
    public function withAttribute($name, $value): self {
        $instance = clone $this;

        $instance->attributes[$name] = $value;

        return $instance;
    }

    /**
     * @param string $name The attribute name.
     * @return static
     */
    public function withoutAttribute($name): self {
        $instance = clone $this;

        unset($instance->attributes[$name]);

        return $instance;
    }

    /**
     * @return array An array tree of UploadedFileInterface instances; an empty
     *     array MUST be returned if no data is present.
     */
    public function getUploadedFiles(): array {
        return $this->uploadedFiles;
    }


    /**
     * @param $tree array
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    private function assertValidUploadedFilesTree(array $tree) {
        foreach ($tree as $leaf) {
            if (is_array($leaf)) {
                $this->assertValidUploadedFilesTree($leaf);
                continue;
            }

            if (! $leaf instanceof UploadedFileInterface) {
                throw new \InvalidArgumentException('An element in this tree must be an instance of ' . UploadedFileInterface::class );
            }
        }
    }


    /**
     * @param array $uploadedFiles
     * @return static
     * @throws \InvalidArgumentException if an invalid structure is provided.
     */
    public function withUploadedFiles(array $uploadedFiles): self {
        $this->assertValidUploadedFilesTree($uploadedFiles);

        $instance = clone $this;

        $instance->uploadedFiles = $uploadedFiles;

        return $instance;
    }

    /**
     * @return null|array|\object The deserialized body parameters, if any.
     *     These will typically be an array or object.
     */
    public function getParsedBody() {
        return $this->parsedBody;
    }


    /**
     * @param null|array|\object $data The deserialized body data. This will
     *     typically be in an array or object.
     * @return static
     * @throws \InvalidArgumentException if an unsupported argument type is
     *     provided.
     */
    public function withParsedBody($data): self {
        $this->assertTypeInList($data, ['array', 'object', 'null']);

        $instance = clone $this;

        $instance->parsedBody = $data;

        return $instance;
    }


    /**
     * @return array
     */
    public function getServerParams(): array {
        return $this->serverParams;
    }
}
