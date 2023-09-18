<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\DataObjects\DataTableQueryParams;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\SimpleCache\CacheInterface;

class CategoryService 
{
    public function __construct(
        private readonly EntityManagerServiceInterface $entityManager,
        private readonly CacheInterface $cache
    )
    {
    }

    public function create(string $name, User $user): Category
    {   
        $category= new Category();
        $category->setUser($user);

        return $this->update($category, $name);
    }

    public function getPaginatedCategories(DataTableQueryParams $params) : Paginator
    {
        $orderBy = in_array($params->orderBy, ['name', 'createdAt', 'updatedAt']) ? $params->orderBy : 'updatedAt';
        $orderDir = strtolower($params->orderDir) === 'asc' ? 'asc' : 'desc';

        $query = $this->entityManager
                    ->getRepository(Category::class)
                    ->createQueryBuilder('c')
                    ->setFirstResult($params->start)
                    ->setMaxResults($params->length)
                    ->orderBy('c.' . $orderBy, $orderDir);

        if(! empty($params->search)) {
            //$search = str_replace(['%','_'],['\%', '\_'], $search);
            $query->where('c.name LIKE :name')->setParameter('name','%' .addcslashes($params->search,'%_') . '%');
        }
       
        return new Paginator($query);
    }

    public function getById(int $id): ?Category
    {
        return $this->entityManager->find(Category::class, $id);
    }

    public function findByName(string $name): ?Category
    {
        return $this->entityManager->getRepository(Category::class)->findBy(['name' => $name])[0] ?? null;
    }
    public function update(Category $category, string $name): Category
    {
        $category->setName($name);

        return $category;
    }

    public function getCategoryNames(): array
    {
        return $this->entityManager->getRepository(Category::class)->createQueryBuilder('c')
                ->select('c.id','c.name')
                ->getQuery()
                ->getArrayResult();
    }

    public function getAllKeyedByName(): array
    {
        // $cacheKey = 'categories_keyed_by_name';
        
        // if($this->cache->has($cacheKey)){
        //     return $this->cache->get($cacheKey);
        // }

        $categories = $this->entityManager->getRepository(Category::class)->findAll();
        $categoryMap =[];

        foreach($categories as $category){
            $categoryMap[strtolower($category->getName())] = $category;
        }

        // $this->cache->set($cacheKey, $categoryMap);

        return $categoryMap;
    }
}