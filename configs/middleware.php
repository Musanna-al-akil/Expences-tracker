<?php

declare(strict_types = 1);

use App\Config;
use App\Middleware\ValidationExceptionMiddleware;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use App\Middleware\StartSessionMiddleWare;
use App\Middleware\ValidationErrorsMiddleware;
use App\Middleware\OldFormDataMiddleware;

return function (App $app) {
    $container = $app->getContainer();
    $config    = $container->get(Config::class);

    // Twig
    $app->add(TwigMiddleware::create($app, $container->get(Twig::class)));

    $app->add(ValidationExceptionMiddleware::class);
    $app->add(ValidationErrorsMiddleware::class);
    $app->add(OldFormDataMiddleware::class);
    $app->add(StartSessionMiddleWare::class);
    
    

    // Logger
    $app->addErrorMiddleware(
        (bool) $config->get('display_error_details'),
        (bool) $config->get('log_errors'),
        (bool) $config->get('log_error_details')
    );
};
