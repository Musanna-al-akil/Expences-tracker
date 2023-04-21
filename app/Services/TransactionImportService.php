<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\DataObjects\TransactionData;
use App\Entity\Transaction;
use App\Entity\User;
use Clockwork\Clockwork;
use Clockwork\Request\LogLevel;

class TransactionImportService
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly TransactionService $transactionService,
        private readonly EntityManagerServiceInterface $entityManagerService,
        private readonly Clockwork $clockwork
    ){
    }

    public function importFromFile(string $file, User $user): void
    {
        $resource   = fopen($file,"r");
        $categories = $this->categoryService->getAllKeyedByName();

        fgetcsv($resource);

       // $this->clockwork->log(LogLevel::DEBUG, 'Memory Usage Before: ' .  memory_get_usage());
       // $this->clockwork->log(LogLevel::DEBUG, 'Unit of Work Before: ' .  $this->entityManagerService->getUnitOfWork()->size());
        $count =1;
        $batchSize = 250;

        while(($row = fgetcsv($resource)) !== false) {
            [$date, $description, $category, $amount] = $row;

            $date       = new \DateTime($date);
            $category   = $categories[strtolower($category)] ?? null;
            $amount     = str_replace(['$',','],'',$amount);

            $transactionData = new TransactionData($description,(float) $amount, $date, $category);

            $csv=$this->transactionService->create($transactionData,$user);
            $this->entityManagerService->persist($csv);

            if($count % $batchSize === 0){
            $this->entityManagerService->sync();
            $this->entityManagerService->clear(Transaction::class);
            $count =1;
            }else{
                $count++;
            }
        }
        if($count >1){
            $this->entityManagerService->sync();
            $this->entityManagerService->clear();
        }

        //garbage collector
        //gc_collect_cycles();

       // $this->clockwork->log(LogLevel::DEBUG, 'Memory Usage After: ' .  memory_get_usage());
       // $this->clockwork->log(LogLevel::DEBUG, 'Unit of Work after: ' .  $this->entityManager->getUnitOfWork()->size());  
    }
}