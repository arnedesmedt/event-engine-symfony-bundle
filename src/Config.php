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

    private EventEngine $eventEngine;
    private AbstractAdapter $cache;
    private string $environment;

    /** @var array<mixed>|null */
    private ?array $config = null;

    public function __construct(EventEngine $eventEngine, AbstractAdapter $cache, string $environment)
    {
        $this->eventEngine = $eventEngine;
        $this->cache = $cache;
        $this->environment = $environment;
    }

    /**
     * @return array<mixed>
     */
    public function config(): array
    {
        if ($this->isDevEnv()) {
            return $this->getConfig();
        }

        return $this->cache->get(
            self::CONFIG,
            function () {
                return $this->getConfig();
            }
        );
    }

    /**
     * @return array<string, string>|string
     */
    public function aggregateIdentifiers(
        ?string $aggregateRootClass = null,
        ?string $defaultAggregateIdentifier = null
    ) {
        $aggregateIdentifiers = $this->cache->get(
            self::AGGREGATE_IDENTIFIERS,
            function () {
                $config = $this->config();

                return array_map(
                    static function (array $aggregateDescription) {
                        return $aggregateDescription['aggregateIdentifier'];
                    },
                    $config['aggregateDescriptions']
                );
            }
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

    /**
     * @return array<mixed>
     */
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
