<?php

declare(strict_types=1);

namespace App\Enums;

enum TokenAbility: string
{
    case REPOSITORY_READ = 'repository:read';
    case REPOSITORY_WRITE = 'repository:write';
    case PACKAGE_UPLOAD = 'package:upload';
    case DEPLOY_TOKEN_CREATE = 'deploy-token:create';

    /**
     * @return TokenAbility[]
     */
    public static function readAbilities(): array
    {
        return [
            self::REPOSITORY_READ,
        ];
    }
}
