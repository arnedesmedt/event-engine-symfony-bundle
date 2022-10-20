<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Command;

use ADS\Util\StringUtil;

use function sprintf;
use function strtoupper;

trait OAuthRoleAuthorization
{
    /**
     * @return array<string>
     */
    public static function __extraAuthorizationCommand(): array
    {
        return [
            sprintf(
                'ROLE_OAUTH2_%s:WRITE',
                strtoupper(StringUtil::entityNameFromClassName(static::class))
            ),
        ];
    }
}
