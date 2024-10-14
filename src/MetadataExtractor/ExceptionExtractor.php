<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\MetadataExtractor;

use ADS\Specifications\Specification;
use LogicException;
use ReflectionClass;
use TeamBlue\Exception\HttpException\HttpException;

use function array_filter;
use function array_unique;
use function file_get_contents;
use function is_subclass_of;
use function preg_match_all;

class ExceptionExtractor
{
    public function __construct(
        private readonly CommandExtractor $commandExtractor,
        private readonly ControllerExtractor $controllerExtractor,
    ) {
    }

    /**
     * @param class-string $className
     *
     * @return array<class-string<HttpException>>
     *
     * @SuppressWarnings(PHPMD.EmptyCatchBlock)
     */
    public function extract(string $className): array
    {
        $reflectionClass = new ReflectionClass($className);

        $classesToExtractExceptionsFor = [$className];

        if ($this->commandExtractor->isCommandFromReflectionClass($reflectionClass)) {
            try {
                $classesToExtractExceptionsFor[] = $this->controllerExtractor->fromReflectionClass($reflectionClass);
            } catch (LogicException) {
            }
        }

        return $this->extractHttpExceptionsFromClasses($classesToExtractExceptionsFor);
    }

    /**
     * @param array<class-string> $classesToExtractExceptionsFor
     *
     * @return array<class-string<HttpException>>
     */
    private function extractHttpExceptionsFromClasses(array $classesToExtractExceptionsFor): array
    {
        $exceptionClasses = [];
        $specificationClasses = [];

        foreach ($classesToExtractExceptionsFor as $class) {
            $specificationClasses = [
                ...$specificationClasses,
                ...$this->importsFromClassByType($class, Specification::class),
            ];
            $exceptionClasses = [
                ...$exceptionClasses,
                ...$this->importsFromClassByType($class, HttpException::class),
            ];
        }

        foreach ($specificationClasses as $specificationClass) {
            $exceptionClasses = [
                ...$exceptionClasses,
                ...$this->importsFromClassByType($specificationClass, HttpException::class),
            ];
        }

        /** @var array<class-string<HttpException>> $uniqueExceptions */
        $uniqueExceptions = array_unique($exceptionClasses);

        return $uniqueExceptions;
    }

    /**
     * @param class-string       $className
     * @param class-string<Type> $type
     *
     * @return array<class-string<Type>>
     *
     * @template Type
     */
    private function importsFromClassByType(string $className, string $type): array
    {
        $reflectionClass = new ReflectionClass($className);

        $filePath = $reflectionClass->getFileName();
        if ($filePath === false) {
            return [];
        }

        $importClasses = $this->importClassesFromFile($filePath);

        /** @var array<class-string<Type>> $specificationClasses */
        $specificationClasses = array_filter(
            $importClasses,
            static fn ($importClass): bool => is_subclass_of($importClass, $type),
        );

        return $specificationClasses;
    }

    /** @return array<class-string> */
    private function importClassesFromFile(string $filePath): array
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            return [];
        }

        $pattern = '/^use\s+([a-zA-Z0-9_\\\\]+)(\s+as\s+[a-zA-Z0-9_\\\\]+)?\s*;/m';
        preg_match_all($pattern, $content, $matches);

        /** @var array<class-string> $importClasses */
        $importClasses = $matches[1];

        return $importClasses;
    }
}
