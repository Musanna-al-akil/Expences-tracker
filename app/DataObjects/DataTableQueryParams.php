<?php

namespace App\DataObjects;

use App\Enum\SameSite;

class DataTableQueryParams
{
    public function __construct(
        public readonly int $start,
        public readonly int $length,
        public readonly string $orderBy,
        public readonly string $orderDir,
        public readonly string $search,
        public readonly int $draw,

    ){ 
    }
}