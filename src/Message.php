<?php

namespace Psr7HttpMessage;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @class Message
 * @implements @MessageInterface
 */
class Message implements MessageInterface
{
    use MessageTrait;

    public function __construct(StreamInterface $body) {
        $this->body = $body;
    }
}