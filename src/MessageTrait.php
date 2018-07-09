<?php

namespace Psr7HttpMessage;

use Psr\Http\Message\StreamInterface;

/**
 * @trait MessageTrait
 */
trait MessageTrait
{
    use AssertionTrait;

    /**
     * @var array[]
     */
    private $headers = [];

    /**
     * @var array
     */
    private $headerAssoc = [];

    /**
     * @var StreamInterface
     */
    private $body;

    /**
     * @var string
     */
    private $protocolVersion = '1.1';

    /**
     * @param string $name
     * @return string
     */
    private function normalizeHeader(string $name): string {
        return strtolower($name);
    }

    /**
     * @param string $name
     * @param string|string[] $value
     */
    private function addHeader(string $name, $value): void {
        $nName = $this->normalizeHeader($name);

        $this->headerAssoc[$nName] = $name;
        $this->headers[$name] = $this->filterHeaderValues(is_array($value) ? $value : [$value]);
    }

    /**
     * @param string $name
     * @param string|string[] $value
     */
    private function addHeaderValue(string $name, $value): void {
        $nName = $this->normalizeHeader($name);

        $oldOrigName = $this->headerAssoc[$nName];
        $oldValue = $this->headers[$oldOrigName];
        $newValue = array_merge($oldValue, $this->filterHeaderValues(is_array($value) ? $value : [$value]));

        unset($this->headers[$oldOrigName]);

        $this->headerAssoc[$nName] = $name;
        $this->headers[$name] = $newValue;
    }

    /**
     * @param string $name
     * @param string|string[] $value
     */
    private function replaceHeader(string $name, $value): void {
        $nName = $this->normalizeHeader($name);

        $oldOrigName = $this->headerAssoc[$nName];
        unset($this->headers[$oldOrigName]);

        $this->headerAssoc[$nName] = $name;
        $this->headers[$name] = $this->filterHeaderValues(is_array($value) ? $value : [$value]);
    }

    /**
     * @param string $name
     */
    private function removeHeader(string $name): void {
        $nName = $this->normalizeHeader($name);
        $origName = $this->headerAssoc[$nName];

        unset($this->headerAssoc[$nName]);
        unset($this->headers[$origName]);
    }

    /**
     * @param string $name Case-insensitive header field name.
     * @return string[]
     */
    public function getHeader($name): array {
        if (!$this->hasHeader($name)) {
            return [];
        }

        $nName = $this->normalizeHeader($name);
        $origName = $this->headerAssoc[$nName];
        return $this->headers[$origName];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasHeader($name): bool {
        $nName = $this->normalizeHeader($name);
        return array_key_exists($nName, $this->headerAssoc);
    }

    /*
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
    */
    public function withHeader($name, $value): self {
        $this->assertTypeInList($name, ['string']);
        $this->assertTypeInList($value, ['string', 'array']);

        $instance = clone $this;

        if (!$instance->hasHeader($name)) {
            $instance->addHeader($name, $value);
        } else {
            $instance->replaceHeader($name, $value);
        }

        return $instance;
    }

    /**
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws \InvalidArgumentException for invalid header names or values.
     */
    public function withAddedHeader($name, $value): self {
        $this->assertTypeInList($name, ['string']);
        $this->assertTypeInList($value, ['string', 'array']);

        $instance = clone $this;

        if (!$instance->hasHeader($name)) {
            $instance->addHeader($name, $value);
        } else {
            $instance->addHeaderValue($name, $value);
        }

        return $instance;
    }

    /**
     * @return string[][]
     */
    public function getHeaders(): array {
        return $this->headers;
    }

    /**
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name): self {
        $instance = clone $this;

        if ($instance->hasHeader($name)) {
            $instance->removeHeader($name);
        }

        return $instance;
    }

    /**
     * @param string $name Case-insensitive header field name.
     * @return string
     */
    public function getHeaderLine($name): string {
        return implode(", ", $this->getHeader($name));
    }

    /**
     * @return StreamInterface
     */
    public function getBody(): StreamInterface {
        return $this->body;
    }

    /**
     * @return string
     */
    public function getProtocolVersion(): string {
        return $this->protocolVersion;
    }

    /**
     * @param StreamInterface $body Body.
     * @return static
     * @throws \InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body): self {
        $this->assertTypeInList($body, [StreamInterface::class]);

        $instance = clone $this;
        $instance->body = $body;

        return $instance;
    }

    /**
     * @param string $version HTTP protocol version
     * @return static
     */
    public function withProtocolVersion($version): self {
        $instance = clone $this;

        $instance->protocolVersion = $version;

        return $instance;
    }

    /**
     * @param $values array
     * @return array
     */
    private function filterHeaderValues(array $values): array {
        return array_map(function($val) {
            return trim($val, '\t ');
        }, $values);
    }

    /**
     * @param $inputHeaders array
     */
    private function setHeaders(array $inputHeaders): void {
        $this->headers = $this->headerAssoc = [];

        foreach ($inputHeaders as $headerName => $headerValue) {
            if ($this->hasHeader($headerName)) {
                $this->addHeaderValue($headerName, $headerValue);
            } else {
                $this->addHeader($headerName, $headerValue);
            }
        }
    }
}