<?php

namespace App\DataObjects;

class UserProfileData
{
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly bool $twoFactor
    ) {
    }
}