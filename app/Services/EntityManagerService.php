<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 *  @mixin EntityManagerInterface
 */
class EntityManagerService implements EntityManagerServiceInterface
{
    public function __construct(protected readonly EntityManagerInterface $entityManager)
    {
    }

    public function __call(string $name, array $arguments)
    {
        if(method_exists($this->entityManager, $name)) {
            return call_user_func_array([$this->entityManager, $name], $arguments);
        }else{
            
            throw new \BadMethodCallException('call to undefined method "' . $name . '"');
        }
    }

    public function sync($entry = null):void
    {
        if($entry){
            $this->entityManager->persist($entry);
        }
        $this->entityManager->flush();
    }

    public function delete($entry, bool $sync = false):void
    {
        $this->entityManager->remove($entry);
        if($sync){
            $this->sync();
        }
    }

    public function clear(?string $entityName = null): void
    {
        if($entityName === null){
            $this->entityManager->clear();
            return;
        }
        
        $unitOfwork = $this->entityManager->getUnitOfWork();
        $entities   = $unitOfwork->getIdentityMap()[$entityName] ?? [];

        foreach($entities as $entity) {
            $this->entityManager->detach($entity);
        }
    }
}