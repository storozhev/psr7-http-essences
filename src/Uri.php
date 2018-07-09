<?php

namespace Psr7HttpMessage;

use Psr\Http\Message\UriInterface;

/**
 * @class Uri
 * @implements UriInterface
 */
class Uri implements UriInterface
{
    use UriTrait;

    /**
     * @var string
     */
    private $scheme;

    /**
     * @var string
     */
    private $host;

    /**
     * @var ?int
     */
    private $port;

    /**
     * @var string
     */
    private $userInfo;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $query;

    /**
     * @var string
     */
    private $fragment;

    /**
     * @const array
     */
    private const DEFAULT_SCHEME_PORT = [
        'http' =>  80,
        'https' => 443,
        'pop' => 110,
    ];

    /**
     * @const string
     */
    private const /** @noinspection PhpUnusedPrivateFieldInspection */ URI_CHAR_SUB_DELIMITERS  = "!\$&\'\(\)\*\+,;=";

    /**
     * @const string
     */
    private const /** @noinspection PhpUnusedPrivateFieldInspection */ URI_CHAR_UNRESERVED = "\w+\-\.~";

    /**
     * @param string $uri
     * @throws \InvalidArgumentException
     */
    public function __construct(string $uri = '') {
        $this->assertTypeInList($uri, ['string']);

        $this->apply($this->parse($uri));
    }


    /**
     * @return string The URI scheme.
     */
    public function getScheme(): string {
        return $this->scheme;
    }

    /**
     * @param string $scheme The scheme to use with the new instance.
     * @return static A new instance with the specified scheme.
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public function withScheme($scheme): self {
        $this->assertTypeInList($scheme, ['string']);

        $scheme = $this->normalizeScheme($scheme);

        $instance = clone $this;

        $instance->scheme = $scheme;

        return $instance;

    }

    /**
     * @return string The URI path.
     */
    public function getPath(): string {
        return $this->path;
    }

    /**
     * @param string $path The path to use with the new instance.
     * @return static A new instance with the specified path.
     * @throws \InvalidArgumentException for invalid paths.
     */
    public function withPath($path): self  {
        $this->assertTypeInList($path, ['string']);

        $path = $this->normalizePath($path);

        if ($path === $this->path) {
            return $this;
        }

        $instance = clone $this;

        $instance->path = $path;

        return $instance;
    }

    /**
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     * @return string The URI host.
     */
    public function getHost(): string {
        return $this->host;
    }


    /**
     * @param string $host The hostname to use with the new instance.
     * @return static A new instance with the specified host.
     * @throws \InvalidArgumentException for invalid hostnames.
     */
    public function withHost($host): self {
        $this->assertTypeInList($host, ['string']);

        $host = $this->normalizeHost($host);

        if ($this->host === $host) {
            return $this;
        }

        $instance = clone $this;

        $instance->host = $host;

        return $instance;
    }

    /**
     * @return null|int The URI port.
     */
    public function getPort(): ?int {
        if (empty($this->scheme) && empty($this->port)) {
            return null;
        }

        if (empty($this->port) && !empty($this->scheme)) {
            return null;
        }

        if (!empty($this->port)) {
            return static::DEFAULT_SCHEME_PORT[$this->scheme] !== $this->port ? $this->port : null;
        }

        return null;
    }

    /**
     * @param null|int $port The port to use with the new instance; a null value
     *     removes the port information.
     * @return static A new instance with the specified port.
     * @throws \InvalidArgumentException for invalid ports.
     */
    public function withPort($port): self {
        $this->assertTypeInList($port, ['integer', 'null']);

        if (is_numeric($port)) {
            $this->assertInRange($port, 1, 65535);
        }

        if ($port === $this->port) {
            return $this;
        }

        $instance = clone $this;

        $instance->port = $port;

        return $instance;
    }

    /**
     * @return string The URI fragment.
     */
    public function getFragment(): string {
        return $this->fragment;
    }


    /***
     * @param string $fragment The fragment to use with the new instance.
     * @return static A new instance with the specified fragment.
     */
    public function withFragment($fragment): self {
        $this->assertTypeInList($fragment, ['string']);

        $fragment = $this->normalizeFragmentAndQuery($fragment);

        if ($fragment === $this->fragment) {
            return $this;
        }

        $instance = clone $this;

        $instance->fragment = $fragment;

        return $instance;
    }

    /**
     * @return string
     */
    public function getQuery(): string {
        return $this->query;
    }

    /**
     * @param string $query The query string to use with the new instance.
     * @return static A new instance with the specified query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public function withQuery($query): self {
        $this->assertTypeInList($query, ['string']);

        $query = $this->normalizeFragmentAndQuery($query);

        if ($query === $this->query) {
            return $this;
        }

        $instance = clone $this;

        $instance->query = $query;

        return $instance;
    }

    /**
     * @return string
     */
    public function getUserInfo(): string {
        return $this->userInfo;
    }

    /**
     * @param string $user The user name to use for authority.
     * @param null|string $password The password associated with $user.
     * @return static A new instance with the specified user information.
     */
    public function withUserInfo($user, $password = null): self {
        $userInfo = $user;
        if (!empty($password)) {
            $userInfo .= ':' . $password;
        }

        if ($userInfo === $this->userInfo) {
            return $this;
        }

        $instance = clone $this;

        $instance->userInfo = $userInfo;

        return $instance;
    }


    /**
     * @return string
     */
    public function __toString(): string {
        $uri = '';
        $authority = $this->getAuthority();
        $query = $this->getQuery();
        $path = $this->getPath();
        $fragment = $this->getFragment();

        if (!empty($this->scheme)) {
            $uri .= $this->scheme . ':';
        }

        if (!empty($authority)) {
            $uri .= '//' . $authority;
        }

        $uri .= $path;

        if (!empty($query)) {
            $uri .= '?' . $query;
        }

        if (!empty($fragment)) {
            $uri .= '#' . $fragment;
        }

        return $uri;
    }


    /**
     * @return string The URI authority, in "[user-info@]host[:port]" format.
     */
    public function getAuthority(): string {
        if (empty($this->host)) {
            return '';
        }

        $authority = $this->host;

        if (!empty($this->userInfo)) {
            $authority = $this->userInfo . "@" . $authority;
        }

        if (!empty($this->port) && static::DEFAULT_SCHEME_PORT[$this->scheme] != $this->port) {
            $authority .= ":" . $this->port;
        }

        return $authority;
    }
}