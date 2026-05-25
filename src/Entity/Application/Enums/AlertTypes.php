<?php

declare(strict_types=1);

namespace App\Entity\Application\Enums;

enum AlertTypes: string
{
    case Success = 'success';
    case Danger = 'danger';
    case Warning = 'warning';
    case Info = 'info';
}
