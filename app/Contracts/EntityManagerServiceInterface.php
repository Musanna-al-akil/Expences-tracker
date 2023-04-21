<?php

declare(strict_types = 1);

namespace App\Contracts;

use Doctrine\ORM\EntityManagerInterface;

/**
 *  @mixin EntityManagerInterface
 */
interface EntityManagerServiceInterface
{
    public function __call(string $name, array $arguments);

    public function sync($entry = null): void;

    public function delete($entry, bool $sync = false): void;

    public function clear(?string $entityName = null): void;
}