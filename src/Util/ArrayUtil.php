<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Util;

use Closure;
use stdClass;

use function array_keys;
use function count;
use function is_array;
use function is_int;
use function range;

final class ArrayUtil
{
    /**
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    private static function process(
        array $array,
        ?Closure $keyClosure = null,
        ?Closure $valueClosure = null,
        bool $recursive = false
    ): array {
        $processedArray = [];

        foreach ($array as $key => $value) {
            if ($recursive) {
                $isStdClass = $value instanceof stdClass;

                if ($isStdClass) {
                    $value = (array) $value;
                }

                if (is_array($value)) {
                    $value = self::process($value, $keyClosure, $valueClosure, $recursive);
                }

                $value = $isStdClass ? (object) $value : $value;
            }

            $processedArray[$keyClosure ? $keyClosure($key) : $key] = $valueClosure ? $valueClosure($value) : $value;
        }

        return $processedArray;
    }

    /**
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    public static function toCamelCasedKeys(array $array, bool $recursive = false): array
    {
        return self::process(
            $array,
            static fn ($key) => is_int($key) ? $key : StringUtil::camelize($key),
            null,
            $recursive
        );
    }

    /**
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    public static function toSnakeCasedKeys(array $array, bool $recursive = false): array
    {
        return self::process(
            $array,
            static fn ($key) => is_int($key) ? $key : StringUtil::decamilize($key),
            null,
            $recursive
        );
    }

    /**
     * @param array<int|string, mixed> $array
     *
     * @return array<int|string, mixed>
     */
    public static function toSnakeCasedValues(array $array, bool $recursive = false): array
    {
        return self::process(
            $array,
            null,
            static fn ($value) => is_int($value) ? $value : StringUtil::decamilize($value),
            $recursive
        );
    }

    /**
     * @param array<mixed> $array
     */
    public static function isAssociative(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }
}
