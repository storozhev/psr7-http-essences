<?php

namespace Psr7HttpMessage;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @class Response
 * @implements ResponseInterface
 */
class Response implements ResponseInterface
{
    use MessageTrait;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var string
     */
    private $reasonPhrase;

    /**
     * @var array
     */
    private static $statusPhrases = [
        // 1×× Informational
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',

        // 2×× Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',

        // 3×× Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',

        // 4×× Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        444 => 'Connection Closed Without Response',
        451 => 'Unavailable For Legal Reasons',

        // 5×× Server Error
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error',
    ];

    /**
     * @param $body StreamInterface|string|resource
     * @param $statusCode int
     * @param $reasonPhrase string
     * @param $headers array
     * @param $protocolVersion string
     * @throws \InvalidArgumentException
     */
    public function __construct($body, int $statusCode = 200, string $reasonPhrase = '',  array $headers = [], string $protocolVersion = '1.1') {
        $this->assertTypeInList($body, ['string', 'resource', StreamInterface::class]);

        $this->body = $body instanceof StreamInterface ? $body : new Stream($body, 'wb+');
        $this->setStatusCode($statusCode);
        $this->setReasonPhrase($reasonPhrase);
        $this->setHeaders($headers);
        $this->protocolVersion = $protocolVersion;
    }

    /**
     * @param $statusCode int
     * @throws \InvalidArgumentException
     */
    private function setStatusCode(int $statusCode): void {
        $codes = array_keys(static::$statusPhrases);

        $this->assertInRange($statusCode, $codes[0], $codes[count($codes)-1]);

        $this->statusCode = $statusCode;
    }

    /**
     * @param $reasonPhrase string
     */
    private function setReasonPhrase(string $reasonPhrase): void {
        if ('' === $reasonPhrase && array_key_exists($this->statusCode, static::$statusPhrases)) {
            $reasonPhrase = static::$statusPhrases[$this->statusCode];
        }

        $this->reasonPhrase = $reasonPhrase;
    }

    /**
     * @return int Status code.
     */
    public function getStatusCode(): int {
        return $this->statusCode;
    }


    /**
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = ''): self {
        $instance = clone $this;
        $instance->setStatusCode($code);
        $instance->setReasonPhrase($reasonPhrase);

        return $instance;
    }

    /**
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase(): string {
        return $this->reasonPhrase;
    }
}