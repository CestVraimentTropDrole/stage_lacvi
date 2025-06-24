<?php

namespace App\Enum;

enum OrderState: string
{
    case BASKET = 'BASKET';
    case CREATED = 'CREATED';
    case VALIDATE = 'VALIDATE';
    case COMPLETE = 'COMPLETE';

    public function getLabel(): string
    {
        return match($this) {
            self::BASKET => 'Panier',
            self::CREATED => 'En attente',
            self::VALIDATE => 'Validée',
            self::COMPLETE => 'Terminée',
        };
    }
}

?>
