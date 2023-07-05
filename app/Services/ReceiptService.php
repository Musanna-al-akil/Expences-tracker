<?php

declare(strict_types = 1);

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Entity\Receipt;

class ReceiptService 
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManager)
    {
    }
    public function create($transaction, string $fileName, string $storageFilename, string $mediaType): Receipt
    {   
        $receipt = new Receipt();

        $receipt->setTransaction($transaction);
        $receipt->setFilename($fileName);
        $receipt->setStorageFilename($storageFilename);
        $receipt->setCreatedAt(new \DateTime());
        $receipt->setMediaType($mediaType);

        return $receipt;
    }

    public function getById(int $id): ?Receipt
    {
        return $this->entityManager->find(Receipt::class, $id);
    }  
}