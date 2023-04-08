<?php

declare(strict_types = 1);

namespace App\Contracts;

interface SessionInterface
{
    public function start(): void;

    public function save(): void;

    public function isActive(): bool;

    public function get($key, mixed $default =  null): mixed;

    public function put(string $key, int $getId): void;

    public function regenerate(): bool;
    public function forget(string $key): void;
}
