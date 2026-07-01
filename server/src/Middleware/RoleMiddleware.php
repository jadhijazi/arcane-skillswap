<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helpers\ResponseHelper;

class RoleMiddleware implements Middleware
{
    private array $roles;

    public function __construct(array $roles)
    {
        $this->roles = $roles;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $claims = $request->getAttribute('jwt');
        if (! $claims || empty($claims->roles)) {
            $response = new \Slim\Psr7\Response();
            return ResponseHelper::json($response, false, 'Forbidden', null, [])->withStatus(403);
        }

        $userRoles = (array)$claims->roles;
        foreach ($this->roles as $role) {
            if (in_array($role, $userRoles, true)) {
                return $handler->handle($request);
            }
        }

        $response = new \Slim\Psr7\Response();
        return ResponseHelper::json($response, false, 'Forbidden', null, [])->withStatus(403);
    }
}
