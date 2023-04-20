<?php

declare(strict_types = 1);

namespace App\Services;

use App\Entity\Receipt;

class ReceiptService extends EntityManagerService
{
    public function create($transaction, string $fileName, string $storageFilename, string $mediaType): Receipt
    {   
        $receipt = new Receipt();

        $receipt->setTransaction($transaction);
        $receipt->setFilename($fileName);
        $receipt->setStorageFilename($storageFilename);
        $receipt->setCreatedAt(new \DateTime());
        $receipt->setMediaType($mediaType);

        $this->entityManager->persist($receipt);

        return $receipt;
    }

    public function getById(int $id): ?Receipt
    {
        return $this->entityManager->find(Receipt::class, $id);
    }
    public function delete(int $id)
    {
        $transaction = $this->entityManager->find(Receipt::class, $id);

        $this->entityManager->remove($transaction);
    }
}