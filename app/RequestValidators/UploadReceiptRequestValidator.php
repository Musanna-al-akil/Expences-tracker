<?php

declare(strict_types=1);

namespace App\RequestValidators;

use App\Contracts\RequestValidatorInterface;
use App\Exception\ValidationException;
use finfo;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

class UploadReceiptRequestValidator implements RequestValidatorInterface
{
    public function validate(array $data): array
    {
        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $data['receipt'] ?? null;

        //validate uploaded file
        if(! $uploadedFile) {
            throw new ValidationException(['receipt' => ['please select a receipt file']]);
        }

        if($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            throw new ValidationException(['receipt' => ['Failed to upload the receipt file']]);;
        }

        //validate file size
        $maxFileSize = 10 * 1024 * 1024;
        if($uploadedFile->getSize() > $maxFileSize){
            throw new ValidationException(['receipt' => ['Maximum allowed size is 10 MB']]);;
        }

        //validate file name
        $filename = $uploadedFile->getClientFilename();
        
        if(! preg_match('/^[a-zA-Z0-9\s._-]+$/', $filename)) {
            throw new ValidationException(['receipt' => ['invalid filename']]);;
        }

        //validate file type
        $allowedMimeTypes=['image/jpeg','image/png','application/pdf'];
        //$allowedExtension=['pdf','png','jpeg','jpg'];
        $tmpFilePath = $uploadedFile->getStream()->getMetadata('uri');

        if(! in_array($uploadedFile->getClientMediaType(),$allowedMimeTypes)) {
            throw new ValidationException(['receipt' => ['Receipt has to be either an image or pdf document']]);;
        }

        $detector = new FinfoMimeTypeDetector();
        $mimeType = $detector->detectMimeTypeFromFile($tmpFilePath);
        
        if(! in_array($mimeType,$allowedMimeTypes)) {
            throw new ValidationException(['receipt' => ['invalid file type']]);;
        }
        
        /* if(! in_array($this->getMimeType($tmpFilePath),$allowedMimeTypes)) {
            throw new ValidationException(['receipt' => ['invalid file type']]);;
        }*/

        return $data;
    }
    /*
    private function getExtension(string $path): string
    {
        $fileInfo = new finfo(FILEINFO_EXTENSION);
        return $fileInfo->file($path) ?: ' ';
    }

    private function getMimeType(string $path): string
    {
        $fileInfo = new finfo(FILEINFO_MIME_TYPE);
        return $fileInfo->file($path) ?: ' ';
    }

    */
}