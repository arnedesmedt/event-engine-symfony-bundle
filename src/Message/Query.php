<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Message;

interface Query
{
    /**
     * @return class-string|string
     */
    public static function __resolver();

//    /**
//     * @return array<int, ResponseTypeSchema>
//     */
//    public static function __responseSchemasPerStatusCode() : array;
//
//    public static function __defaultStatusCode() : int;
//
//    public static function __defaultResponseSchema() : ResponseTypeSchema;
}
