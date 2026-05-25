<?php

declare(strict_types=1);

namespace App\Entity\Career\Enums;

/**
 * Enum for the different types of company packages that exist.
 */
enum CompanyPackageTypes: string
{
    case Banner = 'banner';
    case Featured = 'featured';
    case Job = 'job';
}
