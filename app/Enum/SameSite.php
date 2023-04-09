<?php

declare(strict_types = 1);

namespace App\Enum;

enum SameSite: string
{
    case Strict = 'strict';
    case Lax    = 'lax';
    case None   = 'none';
}
