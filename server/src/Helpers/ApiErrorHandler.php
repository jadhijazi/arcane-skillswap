<?php
declare(strict_types=1);

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpException;
use Slim\Handlers\ErrorHandler;
use Throwable;
use App\Exceptions\HttpException as AppHttpException;

class ApiErrorHandler extends ErrorHandler
{
    protected function respond(): Response
    {
        $exception = $this->exception;
        $statusCode = 500;
        $message = 'Internal server error';
        $errors = [];

        if ($exception instanceof AppHttpException) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage();
        } elseif ($exception instanceof HttpException) {
            $statusCode = $exception->getCode();
            $message = $exception->getMessage();
        } elseif ($exception instanceof \Exception) {
            $message = $exception->getMessage();
            $statusCode = 400;
        }

        if ($this->displayErrorDetails && $exception instanceof Throwable) {
            $errors['trace'] = $exception->getTraceAsString();
        }

        $response = $this->responseFactory->createResponse($statusCode);
        return ResponseHelper::json($response, false, $message, null, $errors);
    }
}
