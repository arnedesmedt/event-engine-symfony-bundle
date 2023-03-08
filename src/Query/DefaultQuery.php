<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Query;

use ADS\Bundle\EventEngineBundle\Exception\MessageException;
use ADS\Bundle\EventEngineBundle\Response\DefaultResponses;
use ADS\JsonImmutableObjects\JsonSchemaAwareRecordLogic;
use ADS\Util\StringUtil;
use EventEngine\Schema\TypeSchema;

use function class_exists;
use function sprintf;
use function str_replace;
use function strtoupper;
use function substr_count;

trait DefaultQuery
{
    use JsonSchemaAwareRecordLogic;
    use DefaultResponses;

    public static function __resolver(): string
    {
        if (substr_count(static::class, '\\Query\\') > 1) {
            throw MessageException::nestedMessageFolder(static::class, 'Query');
        }

        $resolverClass = str_replace('\\Query\\', '\\Resolver\\', static::class);

        if (! class_exists($resolverClass)) {
            throw MessageException::noHandlerFound(static::class, 'resolver');
        }

        return $resolverClass;
    }

    /** @return array<string, class-string<TypeSchema>> */
    public static function __extraResponseClasses(): array
    {
        return [];
    }

    /** @return array<string> */
    public static function __extraAuthorizationQuery(): array
    {
        return [
            sprintf(
                'ROLE_OAUTH2_%s:READ',
                strtoupper(StringUtil::entityNameFromClassName(static::class)),
            ),
        ];
    }
}
