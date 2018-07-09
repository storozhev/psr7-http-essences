<?php

namespace Psr7HttpMessage;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;

/**
 * @class Request
 * @implements RequestInterface
 */
class Request implements RequestInterface
{
    use MessageTrait;

    /**
     * @var string
     */
    private $method;

    /**
     * @var UriInterface
     */
    private $uri;

    /**
     * @var string|null
     */
    private $requestTarget;

    /**
     * @param $uri UriInterface|string
     * @param $method string|null
     * @param $body StreamInterface|string|resource
     * @param $headers array
     * @param $protocolVersion string
     */
    public function __construct($uri, string $method = null, $body = 'php://memory', array $headers = [], string $protocolVersion = '1.1') {
        $this->uri = $uri instanceof UriInterface ? $uri : new Uri($uri);
        $this->method = $method;
        $this->body = $body instanceof StreamInterface ? $body : new Stream($body);
        $this->headers = $headers;
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * @return string Returns the request method.
     */
    public function getMethod(): string {
        return $this->method;
    }

    /**
     * @param string $method Case-sensitive method.
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method): self {
        $this->assertTypeInList($method, ['string']);

        $instance = clone $this;
        $instance->method = $method;
        return $instance;
    }

    /**
     * @return UriInterface
     */
    public function getUri(): UriInterface {
        return $this->uri;
    }

    /**
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false): self {
        if ($uri === $this->uri) {
            return $this;
        }

        $instance = clone $this;
        $instance->uri = $uri;

        if ($preserveHost && $this->hasHeader('Host')) {
            return $instance;
        }

        $uriHost = $uri->getHost();

        if (empty($uriHost)) {
            return $instance;
        }

        if ($uri->getPort()) {
            $uriHost .= ':' . $uri->getPort();
        }

        $instance->addHeader('Host', $uriHost);

        return $instance;
    }

    /**
     * @return string
     */
    public function getRequestTarget(): string {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        if (!empty($query = $this->uri->getQuery())) {
            $target .= '?' . $query;
        }

        if (empty($target)) {
            $target = '/';
        }

        return $target;
    }

    /**
     * @param mixed $requestTarget
     * @throws \InvalidArgumentException
     * @return static
     */
    public function withRequestTarget($requestTarget): self {
        if (preg_match('/\s/', $requestTarget)) {
            throw new \InvalidArgumentException(
                'Request target can\'t contain whitespaces'
            );
        }

        $instance = clone $this;
        $instance->requestTarget = $requestTarget;

        return $instance;
    }
}