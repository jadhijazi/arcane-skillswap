<?php
declare(strict_types=1);

namespace App\Helpers;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Stream;

class ResponseHelper
{
    public static function json(Response $response, bool $success, string $message = '', $data = null, array $errors = []): Response
    {
        $payload = [
            'success' => $success,
            'message' => $message,
        ];

        if ($success) {
            $payload['data'] = $data ?? (object)[];
        } else {
            $payload['errors'] = $errors;
        }

        $body = new Stream(fopen('php://temp', 'r+'));
        $body->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $response->withHeader('Content-Type', 'application/json')->withBody($body);
    }
}
