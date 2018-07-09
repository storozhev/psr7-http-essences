<?php

namespace Psr7HttpMessage;

/**
 * @trait UriTrait
 */
trait UriTrait
{
    use AssertionTrait;

    /**
     * @param string $scheme
     * @return string
     * @throws \InvalidArgumentException
     */
    private function normalizeScheme(string $scheme): string {
        $scheme = strtolower($scheme);

        if (!array_key_exists($scheme, static::DEFAULT_SCHEME_PORT)) {
            throw new \InvalidArgumentException(sprintf("%s scheme is not supported!", $scheme));
        }

        return $scheme;
    }

    /**
     * @param $path string
     * @return string
     */
    private function normalizePath(string $path) {
        $pattern = '/(?:[^' . static::URI_CHAR_UNRESERVED . static::URI_CHAR_SUB_DELIMITERS . '\/%@:]++|%(?![A-Fa-f0-9]{2}))/';

        return preg_replace_callback($pattern, [$this, 'rawUrlEncode'], $path);
    }

    /**
     * @param string $host
     * @return string
     */
    private function normalizeHost(string $host): string {
        return strtolower($host);
    }


    /**
     * @param string $value
     * @return string
     */
    private function normalizeFragmentAndQuery(string $value): string {
        $pattern = '/(?:[^' . static::URI_CHAR_UNRESERVED . static::URI_CHAR_SUB_DELIMITERS . '\/\?%@:]++|%(?![A-Fa-f0-9]{2}))/';

        return preg_replace_callback($pattern, [$this, 'rawUrlEncode'], $value);
    }

    /**
     * @param $infoPart string
     * @return string
     */
    private function normalizeUserInfo(string $infoPart): string {
        return $infoPart;
    }

    /**
     * @param array $matches
     * @return string
     */
    private function rawUrlEncode(array $matches): string {
        return rawurlencode($matches[0]);
    }



    /**
     * @param string $uri
     * @return \stdClass
     */
    private function parse(string $uri): \stdClass {
        $res = new \stdClass();

        $parsed = parse_url($uri);

        $this->assertTypeInList($parsed, ['array']);

        $res->scheme = array_key_exists('scheme', $parsed) ? $this->normalizeScheme($parsed['scheme']) : '';
        $res->host = array_key_exists('host', $parsed) ? $this->normalizeHost($parsed['host']) : '';
        $res->port = array_key_exists('port', $parsed) ? $parsed['port'] : null;

        $res->userInfo = array_key_exists('user', $parsed) ? $this->normalizeUserInfo($parsed['user']) : '';
        if (array_key_exists('pass', $parsed)) {
            $res->userInfo .= ':' . $parsed['pass'];
        }

        $res->path = array_key_exists('path', $parsed) ? $parsed['path'] : '';
        $res->query = array_key_exists('query', $parsed) ? $parsed['query'] : '';
        $res->fragment = array_key_exists('fragment', $parsed) ? $this->normalizeFragmentAndQuery($parsed['fragment']) : '';

        return $res;
    }

    /**
     * @param \stdClass $obj
     */
    private function apply(\stdClass $obj) {
        foreach ($obj as $key=>$value) {
            $this->{$key} = $value;
        }
    }


}