<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle;

use EventEngine\EventEngine;
use Symfony\Component\Cache\Adapter\AbstractAdapter;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use function array_map;

final class Config implements CacheClearerInterface
{
    public const CONFIG = 'config';
    public const AGGREGATE_IDENTIFIERS = 'aggregateIdentifiers';

    private EventEngine $eventEngine;
    private AbstractAdapter $cache;

    public function __construct(EventEngine $eventEngine, AbstractAdapter $cache)
    {
        $this->eventEngine = $eventEngine;
        $this->cache = $cache;
    }

    /**
     * @return array<mixed>
     */
    public function config() : array
    {
        return $this->cache->get(
            self::CONFIG,
            function () {
                return $this->eventEngine->compileCacheableConfig();
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

    public function clear(string $cacheDir) : void
    {
        $this->cache->clear();
    }
}
