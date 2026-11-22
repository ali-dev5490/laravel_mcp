<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadActivityType: string
{
    case Created = 'created';
    case StatusChange = 'status_change';
    case Note = 'note';
    case Converted = 'converted';
}
