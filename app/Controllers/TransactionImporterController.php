<?php

declare(strict_types = 1);

namespace App\Controllers;

use App\Contracts\RequestValidatorFactoryInterface;
use App\RequestValidators\ImportTransactionsRequestValidator;
use App\Services\TransactionImportService;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
class TransactionImporterController
{
    public function __construct(
        private readonly RequestValidatorFactoryInterface $requestValidatorFactory,
        private readonly TransactionImportService $transactionImportService
    ) {
    } 

    public function import(Request $request, Response $response): Response
    {
        //validate data
        $file = $this->requestValidatorFactory->make(ImportTransactionsRequestValidator::class)->validate($request->getUploadedFiles())['importFile'];
        
        $user       = $request->getAttribute('user');
        
        $this->transactionImportService->importFromFile($file->getStream()->getMetadata('uri'),$user);

        return $response;
    }
}