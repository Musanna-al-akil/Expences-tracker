<?php

declare(strict_types = 1);

namespace App\Services;
use App\DataObjects\DataTableQueryParams;
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

    public function findByName(string $name): ?Category
    {
        return $this->entityManager->getRepository(Category::class)->findBy(['name' => $name])[0] ?? null;
    }
    public function update(Category $category, string $name): Category
    {
        $category->setName($name);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        return $category;
    }

    public function getCategoryNames(): array
    {
        return $this->entityManager->getRepository(Category::class)->createQueryBuilder('c')
                ->select('c.id','c.name')
                ->getQuery()
                ->getArrayResult();
    }
}