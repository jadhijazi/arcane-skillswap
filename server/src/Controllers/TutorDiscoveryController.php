<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\TutorDiscoveryService;
use App\Helpers\ResponseHelper;

class TutorDiscoveryController
{
    private TutorDiscoveryService $discoveryService;

    public function __construct(TutorDiscoveryService $discoveryService)
    {
        $this->discoveryService = $discoveryService;
    }

    public function search(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams();
        try {
            $result = $this->discoveryService->searchTutors($query);
            return ResponseHelper::json($response, true, 'Tutors found', $result);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }
}
