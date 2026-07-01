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
        $token = str_starts_with($auth, 'Bearer ') ? substr($auth, 7) : '';

        if (!$token) {
            $response = new \Slim\Psr7\Response();
            return ResponseHelper::json($response, false, 'Unauthorized - auth_header: ' . substr($auth, 0, 20), null, [])->withStatus(401);
        }

        // Debug: show token structure
        $parts = explode('.', $token);
        $partCount = count($parts);
        $totalLen = strlen($token);
        $p2len = isset($parts[1]) ? strlen($parts[1]) : 0;

        try {
            $claims = $this->jwt->decode($token);
            $request = $request->withAttribute('jwt', $claims);
            return $handler->handle($request);
        } catch (\Throwable $e) {
            $response = new \Slim\Psr7\Response();
            $debug = sprintf(
                '%s | parts:%d | total_len:%d | p2_len:%d | p2_start:%s',
                $e->getMessage(),
                $partCount,
                $totalLen,
                $p2len,
                substr($parts[1] ?? '', 0, 20)
            );
            return ResponseHelper::json($response, false, 'Invalid token: ' . $debug, null, [])->withStatus(401);
        }
    }
}