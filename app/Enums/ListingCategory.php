<?php

declare(strict_types=1);

namespace App\Enums;

enum ListingCategory: string
{
    case Furniture = 'furniture';
    case Lighting = 'lighting';
    case Storage = 'storage';
    case Decor = 'decor';
}
