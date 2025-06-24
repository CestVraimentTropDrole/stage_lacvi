<?php

namespace App\Enum;

enum UserRoles: string
{
    case ROLE_SIGNEDIN = 'ROLE_SIGNEDIN';
    case ROLE_USER = 'ROLE_USER';
    case ROLE_ADMIN = 'ROLE_ADMIN';

    public function getLabel(): string
    {
        return match($this) {
            self::ROLE_SIGNEDIN => 'Inscrit',
            self::ROLE_USER => 'Utilisateur validé',
            self::ROLE_ADMIN => 'Administrateur',
        };
    }

    /**
     * Obtenir tous les rôles disponibles
     */
    public static function getAllRoles(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Vérifier si un rôle est supérieur à un autre
     */
    public function isHigherThan(UserRoles $role): bool
    {
        $hierarchy = [
            self::ROLE_SIGNEDIN->value => 1,
            self::ROLE_USER->value => 2,
            self::ROLE_ADMIN->value => 3,
        ];

        return $hierarchy[$this->value] > $hierarchy[$role->value];
    }

    /**
     * Obtenir le niveau de privilège du rôle
     */
    public function getLevel(): int
    {
        return match($this) {
            self::ROLE_SIGNEDIN => 1,
            self::ROLE_USER => 2,
            self::ROLE_ADMIN => 3,
        };
    }
}