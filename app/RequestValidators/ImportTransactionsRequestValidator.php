<?php

declare(strict_types=1);

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class ImportTransactionsRequestValidator implements RequestValidatorInterface
{
    public function validate(array $data): array
    {
        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $data['importFile'] ?? null;

        //validate uploaded file
        if(! $uploadedFile) {
            throw new ValidationException(['importFile' => ['please select a csv file']]);
        }

        if($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['importFile' => ['Failed to upload the transactions file']]);;
        }

        //validate file size
        $maxFileSize = 10 * 1024 * 1024;
        if($uploadedFile->getSize() > $maxFileSize){
            throw new ValidationException(['importFile' => ['Maximum allowed size is 10 MB']]);;
        }

        //validate file name
        $filename = $uploadedFile->getClientFilename();
        
        if(! preg_match('/^[a-zA-Z0-9\s._-]+$/', $filename)) {
            throw new ValidationException(['importFile' => ['invalid filename']]);;
        }

        //validate file type
        $allowedMimeTypes=['text/csv'];
        
        $tmpFilePath = $uploadedFile->getStream()->getMetadata('uri');

        if(! in_array($uploadedFile->getClientMediaType(),$allowedMimeTypes)) {
            throw new ValidationException(['importFile' => ['File has to be a CSV file type']]);;
        }

        $detector = new FinfoMimeTypeDetector();
        $mimeType = $detector->detectMimeTypeFromFile($tmpFilePath);
        
        if(! in_array($mimeType,$allowedMimeTypes)) {
            throw new ValidationException(['importFile' => ['invalid file type']]);;
        }
        

        return $data;
    }

}