<?php

declare(strict_types = 1);

namespace App;

use Closure;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Csrf
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    public function failureHandler(): Closure
    {
        return function(ServerRequestInterface $request, RequestHandlerInterface $handler){
            return $this->responseFactory->createResponse()->withStatus(403);
        };
    }
}