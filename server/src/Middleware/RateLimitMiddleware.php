<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helpers\ResponseHelper;

class RateLimitMiddleware implements Middleware
{
    private int $limit;
    private int $windowSeconds;

    public function __construct(int $limit = 100, int $windowSeconds = 60)
    {
        $this->limit = $limit;
        $this->windowSeconds = $windowSeconds;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Basic IP-based rate limiting using temp files (demo only)
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'anon';
        $key = sys_get_temp_dir() . '/rl_' . md5($ip);
        $data = ['count' => 0, 'reset' => time() + $this->windowSeconds];
        if (file_exists($key)) {
            $content = json_decode((string)file_get_contents($key), true);
            if (is_array($content)) {
                $data = $content;
            }
        }

        if ($data['reset'] < time()) {
            $data = ['count' => 0, 'reset' => time() + $this->windowSeconds];
        }

        $data['count']++;
        file_put_contents($key, json_encode($data));

        if ($data['count'] > $this->limit) {
            $response = new \Slim\Psr7\Response();
            return ResponseHelper::json($response, false, 'Too many requests', null, [])->withStatus(429);
        }

        return $handler->handle($request);
    }
}
