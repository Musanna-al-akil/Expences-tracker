<?php

namespace App\DataObjects;

use App\Entity\Category;

class TransactionData
{
    public function __construct(
        public readonly string $description,
        public readonly float $amount,
        public readonly \DateTime $date,
        public readonly Category $category
    ){ 
    }
}