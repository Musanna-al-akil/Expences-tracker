<?php

declare(strict_types = 1);

namespace App\Services;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

class CategoryService
{
    public function __construct(private readonly EntityManager $entityManager)
    {
    }
    public function create(string $name, User $user): Category
    {   
        $category= new Category();
        $category->setUser($user);

        return $this->update($category, $name);
    }

    public function getPaginatedCategories(int $start, int $length, string $orderBy, string $orderDir, string $search) : Paginator
    {
        $orderBy = in_array($orderBy, ['name', 'createdAt', 'updatedAt']) ? $orderBy : 'updatedAt';
        $orderDir = strtolower($orderDir) === 'asc' ? 'asc' : 'desc';

        $query = $this->entityManager
                    ->getRepository(Category::class)
                    ->createQueryBuilder('c')
                    ->setFirstResult($start)
                    ->setMaxResults($length)
                    ->orderBy('c.' . $orderBy, $orderDir);

        if(! empty($search)) {
            //$search = str_replace(['%','_'],['\%', '\_'], $search);
            $query->where('c.name LIKE :name')->setParameter('name','%' .addcslashes($search,'%_') . '%');
        }
       
        return new Paginator($query);
    }

    public function delete(int $id): Void
    {
        $category = $this->entityManager->find(Category::class, $id);

        $this->entityManager->remove($category);
        $this->entityManager->flush();
    }

    public function getById(int $id): ?Category
    {
        return $this->entityManager->find(Category::class, $id);
    }

    public function update(Category $category, string $name): Category
    {
        $category->setName($name);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }
}