<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case EMPLOYEE = 'employee';

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrator',
            self::EMPLOYEE => 'Employee'
        };
    }

    public function canManageUsers(): bool
    {
        return match($this) {
            self::ADMIN => true,
            self::EMPLOYEE => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
