<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Classes;

use FilesystemIterator;
use Iterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use ReflectionClass;
use RegexIterator;
use Throwable;

use function get_declared_classes;
use function get_declared_interfaces;
use function preg_match;
use function realpath;
use function sort;

class ReflectionClassRecursiveIterator
{
    private function __construct()
    {
    }

    /**
     * @param array<int, string> $directories
     *
     * @return Iterator<class-string, ReflectionClass<object>>
     */
    public static function fromDirectories(array $directories): Iterator
    {
        foreach ($directories as $path) {
            /** @var Iterator<int, array<int, string>> $iterator */
            $iterator = new RegexIterator(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY,
                ),
                '/^.+\.php$/i',
                RecursiveRegexIterator::GET_MATCH,
            );

            foreach ($iterator as $file) {
                $sourceFile = $file[0];

                if (! preg_match('(^phar:)i', (string) $sourceFile)) {
                    $sourceFile = realpath($sourceFile);
                }

                try {
                    require_once $sourceFile;
                } catch (Throwable) {
                    // invalid PHP file (example: missing parent class)
                    continue;
                }

                $includedFiles[$sourceFile] = true;
            }
        }

        $sortedClasses = get_declared_classes();
        sort($sortedClasses);
        $sortedInterfaces = get_declared_interfaces();
        sort($sortedInterfaces);
        $declared = [...$sortedClasses, ...$sortedInterfaces];
        foreach ($declared as $className) {
            $reflectionClass = new ReflectionClass($className);
            $sourceFile = $reflectionClass->getFileName();
            if (! isset($includedFiles[$sourceFile])) {
                continue;
            }

            yield $className => $reflectionClass;
        }
    }
}
