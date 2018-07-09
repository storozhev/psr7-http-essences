<?php

namespace Psr7HttpMessage;

/**
 * @trait AssertionTrait
 */
trait AssertionTrait
{
    /**
     * @param mixed $arg
     * @param array $types an array of strings
     * @throws \InvalidArgumentException
     */
    protected function assertTypeInList($arg, array $types): void {
        $t = strtolower(gettype($arg));
        $impl = false;

        if ("object" === $t) {
            $t = get_class($arg);

            $iterator = new \ArrayIterator($types);

            iterator_apply($iterator, function(\Iterator $it) use (&$impl, &$arg) {
                if (is_a($arg, $it->current()) || 'object' === $it->current()) {
                    $impl = true;
                    return false;
                }
                return true;
            }, [$iterator]);
        }

        if(!in_array($t, $types) && false === $impl) {
            throw new \InvalidArgumentException(sprintf("%s expected, %s given", implode(" or ", $types), $t));
        }
    }

    /**
     * @param mixed $value
     * @param mixed $from
     * @param mixed $to
     * @param string $message
     * @throws \InvalidArgumentException
     */
    protected function assertInRange($value, $from, $to, string $message = "") {
        if (empty($message)) {
            $message = "The value %v is outside the range (%v, %v)";
        }

        if ($value < $from || $value > $to) {
            throw new \InvalidArgumentException(sprintf($message, $value, $from, $to));
        }
    }
}