<?php

declare(strict_types = 1);

namespace App\Contracts;

interface SessionInterface
{
    public function start(): void;

    public function save(): void;

    public function isActive(): bool;

    public function get($key, mixed $default =  null): mixed;

    public function put(string $key, string|int $getId): void;

    public function regenerate(): bool;

    public function forget(string $key): void;

    public function flash(string $key, array $messages): void;

    public function getflash(string $key): array;
}