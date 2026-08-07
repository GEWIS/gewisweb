<?php

declare(strict_types=1);

namespace App\Entity\Education\Enums;

enum DownloadStatus: string
{
    case Pending = 'pending';
    case Ready = 'ready';
    case Failed = 'failed';
}
