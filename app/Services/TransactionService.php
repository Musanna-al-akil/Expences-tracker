<?php

declare(strict_types = 1);

namespace App\Services;

use App\DataObjects\DataTableQueryParams;
use App\DataObjects\TransactionData;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TransactionService
{
    public function __construct(private readonly EntityManager $entityManager)
    {
    }
    public function create(TransactionData $transactiondata, User $user): Transaction
    {   
        $transaction= new Transaction();
        $transaction->setUser($user);

        return $this->update($transaction, $transactiondata);
    }

    public function getPaginatedTransactions(DataTableQueryParams $params) : Paginator
    {
        $orderBy = in_array($params->orderBy, ['description', 'amount', 'date','category']) ? $params->orderBy : 'date';
        $orderDir = strtolower($params->orderDir) === 'asc' ? 'asc' : 'desc';

   
        $query = $this->entityManager
                    ->getRepository(Transaction::class)
                    ->createQueryBuilder('t')
                    ->select('t','c','r')
                    ->leftJoin('t.category','c')
                    ->leftJoin('t.receipts', 'r')
                    ->setFirstResult($params->start)
                    ->setMaxResults($params->length);
                    

        if(! empty($params->search)) {
            //$search = str_replace(['%','_'],['\%', '\_'], $search);
            $query->where('t.description LIKE :description')->setParameter('description','%' .addcslashes($params->search,'%_') . '%');
        }
       
        if($orderBy === 'category'){
            $query->orderBy('c.name', $orderDir);
        }else{  
            $query->orderBy('t.' . $orderBy, $orderDir);
        }
        return new Paginator($query);
    }

    public function delete(int $id): Void
    {
        $transaction = $this->entityManager->find(Transaction::class, $id);

        $this->entityManager->remove($transaction);
        $this->entityManager->flush();
    }

    public function getById(int $id): ?Transaction
    {
        return $this->entityManager->find(Transaction::class, $id);
    }

    public function update(Transaction $transaction, TransactionData $transactionData): Transaction
    {
        $transaction->setDescription($transactionData->description);
        $transaction->setAmount($transactionData->amount);
        $transaction->setDate($transactionData->date);
        $transaction->setCategory($transactionData->category);

        $this->entityManager->persist($transaction);

        return $transaction;
    }

}