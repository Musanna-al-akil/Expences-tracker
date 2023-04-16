<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\DataObjects\TransactionData;
use App\RequestValidators\ImportTransactionsRequestValidator;
use App\Services\CategoryService;
use App\Services\TransactionService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
class TransactionImporterController
{
    public function __construct(
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly CategoryService $categoryService,
        private readonly TransactionService $transactionService
    ) {
    } 

    public function import(Request $request, Response $response): Response
    {
        //validate data
        $file = $this->requestValidatorFactory->make(ImportTransactionsRequestValidator::class)->validate($request->getUploadedFiles())['importFile'];
        
        $user       = $request->getAttribute('user');
        $resource   = fopen($file->getStream()->getMetadata('uri'),"r");
        $categories = $this->categoryService->getAllKeyedByName();

        fgetcsv($resource);
        while(($row = fgetcsv($resource)) !== false) {
            [$date, $description, $category, $amount] = $row;

            $date       = new \DateTime($date);
            $category   = $categories[strtolower($category)] ?? null;
            $amount     = str_replace(['$',','],'',$amount);

            $transactionData = new TransactionData($description,(float) $amount, $date, $category);

            $this->transactionService->create($transactionData,$user);
        }

        return $response;
    }
}