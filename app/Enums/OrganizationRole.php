<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case MEMBER = 'member';
    case ADMIN = 'admin';
    case MANAGER = 'manager';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
