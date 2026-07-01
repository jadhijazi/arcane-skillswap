<?php
declare(strict_types=1);

namespace App\Bootstrap;

use DI\ContainerBuilder;
use Slim\Factory\AppFactory as SlimAppFactory;
use App\Helpers\ApiErrorHandler;

class AppFactory
{
    public static function createApp(): \Slim\App
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(__DIR__ . '/../app/dependencies.php');
        $container = $containerBuilder->build();

        SlimAppFactory::setContainer($container);
        $app = SlimAppFactory::create();

        $settings = $container->get('settings');
        $displayErrorDetails = $settings['displayErrorDetails'] ?? false;

        $app->addRoutingMiddleware();
        $app->addBodyParsingMiddleware();

        $errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);
        $errorHandler = new ApiErrorHandler(
            $app->getCallableResolver(),
            $app->getResponseFactory()
        );
        $errorMiddleware->setDefaultErrorHandler($errorHandler);

        $routes = __DIR__ . '/../src/Routes/api.php';
        if (file_exists($routes)) {
            (require $routes)($app);
        }

        return $app;
    }
}
