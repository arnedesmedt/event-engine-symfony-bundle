<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle;

use EventEngine\EventEngine;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;

use function array_map;
use function is_array;
use function preg_match;

final class Config implements CacheClearerInterface
{
    public const CONFIG = 'config';

    public const AGGREGATE_IDENTIFIERS = 'aggregateIdentifiers';

    /** @var array<string, array<string, string>>|null */
    private array|null $config = null;

    public function __construct(
        private readonly EventEngine $eventEngine,
        private readonly AbstractAdapter $cache,
        private readonly string $environment,
    ) {
    }

    /** @return array<string, array<mixed>> */
    public function config(): array
    {
        if ($this->isDevEnv()) {
            return $this->getConfig();
        }

        /** @var array<string, array<mixed>> $result */
        $result = $this->cache->get(
            self::CONFIG,
            fn (): array => $this->getConfig()
        );

        return $result;
    }

    /** @return array<string, string>|string */
    public function aggregateIdentifiers(
        string|null $aggregateRootClass = null,
        string|null $defaultAggregateIdentifier = null,
    ): array|string|null {
        /** @var array<string, string> $aggregateIdentifiers */
        $aggregateIdentifiers = $this->cache->get(
            self::AGGREGATE_IDENTIFIERS,
            function (): array {
                /** @var array<string, array<string, array<string, mixed>>> $config */
                $config = $this->config();

                return array_map(
                    static fn (array $aggregateDescription): mixed => $aggregateDescription['aggregateIdentifier'],
                    $config['aggregateDescriptions'],
                );
            },
        );

        if ($aggregateRootClass === null) {
            return $aggregateIdentifiers;
        }

        return $aggregateIdentifiers[$aggregateRootClass] ?? $defaultAggregateIdentifier;
    }

    public function clear(string $cacheDir): void
    {
        $this->cache->clear();
    }

    /** @return array<string, array<string, string>> */
    private function getConfig(): array
    {
        if (is_array($this->config)) {
            return $this->config;
        }

        $this->config = $this->eventEngine->compileCacheableConfig();

        return $this->config;
    }

    private function isDevEnv(): bool
    {
        return preg_match('/(dev(.*)|local)/i', $this->environment) === 1;
    }
}
