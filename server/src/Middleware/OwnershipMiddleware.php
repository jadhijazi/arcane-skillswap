<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helpers\ResponseHelper;

class OwnershipMiddleware implements Middleware
{
    /** @var callable(Request, array<string, mixed>): bool */
    private $ownershipChecker;

    /**
     * @param callable(Request, array<string, mixed>): bool $ownershipChecker
     */
    public function __construct(callable $ownershipChecker)
    {
        $this->ownershipChecker = $ownershipChecker;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $jwt = $request->getAttribute('jwt');
        if (!$jwt || empty($jwt->sub)) {
            $response = new \Slim\Psr7\Response();
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $route = $request->getAttribute('__route__');
        $args = $route ? $route->getArguments() : [];

        if (!($this->ownershipChecker)($request, $args)) {
            $response = new \Slim\Psr7\Response();
            return ResponseHelper::json($response, false, 'Forbidden', null, [])->withStatus(403);
        }

        return $handler->handle($request);
    }
}
