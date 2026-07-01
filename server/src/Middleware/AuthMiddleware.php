<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helpers\JwtHelper;
use App\Helpers\ResponseHelper;

class AuthMiddleware implements Middleware
{
    private JwtHelper $jwt;

    public function __construct(JwtHelper $jwt)
    {
        $this->jwt = $jwt;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $auth = $request->getHeaderLine('Authorization');
        if (! $auth || ! str_starts_with($auth, 'Bearer ')) {
            $response = new \Slim\Psr7\Response();
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $token = substr($auth, 7);
        try {
            $claims = $this->jwt->decode($token);
            $request = $request->withAttribute('jwt', $claims);
            return $handler->handle($request);
        } catch (\Throwable $e) {
            $response = new \Slim\Psr7\Response();
            return ResponseHelper::json($response, false, 'Invalid token', null, [])->withStatus(401);
        }
    }
}
