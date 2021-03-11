<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector;

use ADS\Util\StringUtil;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\Messaging\Message;
use EventEngine\Projecting\AggregateProjector;
use LogicException;

use function get_class;
use function in_array;
use function is_string;
use function preg_replace;
use function strrpos;
use function substr;

abstract class DefaultProjector implements Projector
{
    protected DocumentStore $documentStore;

    public function __construct(DocumentStore $documentStore)
    {
        $this->documentStore = $documentStore;
    }

    public function prepareForRun(string $projectionVersion, string $projectionName): void
    {
        if ($this->documentStore->hasCollection(static::generateCollectionName($projectionVersion, $projectionName))) {
            return;
        }

        $this->documentStore->addCollection(static::generateCollectionName($projectionVersion, $projectionName));
    }

    public function deleteReadModel(string $projectionVersion, string $projectionName): void
    {
        $this->documentStore->dropCollection(static::generateCollectionName($projectionVersion, $projectionName));
    }

    public static function projectionName(): string
    {
        $className = static::class;

        $lastPartOfClassName = self::getLastPartOfClassName($className);

        $cleanedClassName = preg_replace('/Projector$/', '', $lastPartOfClassName);
        if (! is_string($cleanedClassName)) {
            throw new LogicException('Unable to remove Projector from: ' . $lastPartOfClassName);
        }

        return StringUtil::decamelize($cleanedClassName);
    }

    public static function version(): string
    {
        return '0.1.0';
    }

    public static function generateOwnCollectionName(): string
    {
        return self::generateCollectionName(static::version(), static::projectionName());
    }

    public static function stateClassName(): string
    {
        $className = static::class;

        $stateClassName = preg_replace('/(\w*)$/m', 'State', $className, 1);
        if (! is_string($stateClassName)) {
            throw new LogicException('Unable to generate state name from: ' . $className);
        }

        return $stateClassName;
    }

    protected static function generateCollectionName(string $projectionVersion, string $projectionName): string
    {
        return AggregateProjector::generateCollectionName($projectionVersion, $projectionName);
    }

    private static function getLastPartOfClassName(string $className): string
    {
        $lastPartOfClassName = substr($className, strrpos($className, '\\') + 1);
        if (! is_string($lastPartOfClassName)) {
            throw new LogicException('Unable to get last part of class name from ' . $className);
        }

        return $lastPartOfClassName;
    }

    /**
     * @param mixed $event
     */
    public function handle(string $projectionVersion, string $projectionName, $event): void
    {
        $eventClass = get_class($event);
        if ($event instanceof Message) {
            $eventClass = $event->messageName();
            $event = $event->get('message');
        }

        if (! in_array($eventClass, static::events(), true)) {
            return;
        }

        $eventMethod = 'when' . self::getLastPartOfClassName($eventClass);

        $this->{$eventMethod}($event);
    }
}
