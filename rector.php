<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\LevelSetList;

//phpcs:disable Squiz.Arrays.ArrayDeclaration.NoKeySpecified
return static function (RectorConfig $rectorConfig): void {
    // get parameters
    $rectorConfig->paths(
        [
            __DIR__ . '/src',
            __DIR__ . '/tests',
        ],
    );
    $rectorConfig->phpVersion(PhpVersion::PHP_81);
    $rectorConfig->import(LevelSetList::UP_TO_PHP_81);

    $rectorConfig->skip(
        [
            ReadOnlyPropertyRector::class => [
                __DIR__ . '/tests',
            ],
            FirstClassCallableRector::class => [
                __DIR__ . '/src/EventEngineFactory.php',
            ],
            __DIR__ . '/src/Persistency/PDO.php',
        ],
    );
};
