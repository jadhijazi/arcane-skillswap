<?php
declare(strict_types=1);

namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Services\BookingService;
use App\Helpers\ResponseHelper;

class BookingController
{
    private BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function create(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $data = (array)$request->getParsedBody();
        try {
            $booking = $this->bookingService->requestBooking($userId, $data);
            return ResponseHelper::json($response, true, 'Booking requested', ['booking' => $booking])->withStatus(201);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $booking = $this->bookingService->getBooking((int)$args['id']);
            return ResponseHelper::json($response, true, 'Booking found', ['booking' => $booking]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(404);
        }
    }

    public function accept(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        try {
            $booking = $this->bookingService->getBooking((int)$args['id']);
            if ($booking->tutor_id !== $userId) {
                return ResponseHelper::json($response, false, 'Forbidden', null, [])->withStatus(403);
            }
            $booking = $this->bookingService->acceptBooking((int)$args['id']);
            return ResponseHelper::json($response, true, 'Booking accepted', ['booking' => $booking]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function decline(Request $request, Response $response, array $args): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        try {
            $booking = $this->bookingService->getBooking((int)$args['id']);
            if ($booking->tutor_id !== $userId) {
                return ResponseHelper::json($response, false, 'Forbidden', null, [])->withStatus(403);
            }
            $booking = $this->bookingService->declineBooking((int)$args['id']);
            return ResponseHelper::json($response, true, 'Booking declined', ['booking' => $booking]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function confirm(Request $request, Response $response, array $args): Response
    {
        $booking = $this->bookingService->getBooking((int)$args['id']);
        try {
            $booking = $this->bookingService->confirmBooking((int)$args['id']);
            return ResponseHelper::json($response, true, 'Booking confirmed', ['booking' => $booking]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function complete(Request $request, Response $response, array $args): Response
    {
        try {
            $booking = $this->bookingService->completeBooking((int)$args['id']);
            return ResponseHelper::json($response, true, 'Booking completed', ['booking' => $booking]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function cancel(Request $request, Response $response, array $args): Response
    {
        try {
            $booking = $this->bookingService->cancelBooking((int)$args['id']);
            return ResponseHelper::json($response, true, 'Booking cancelled', ['booking' => $booking]);
        } catch (\Exception $e) {
            return ResponseHelper::json($response, false, $e->getMessage(), null, [])->withStatus(400);
        }
    }

    public function getLearnerBookings(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 50);
        $result = $this->bookingService->getLearnerBookings($userId, $page, $perPage);
        return ResponseHelper::json($response, true, 'Bookings retrieved', $result);
    }

    public function getTutorBookings(Request $request, Response $response): Response
    {
        $jwt = $request->getAttribute('jwt');
        $userId = $jwt->sub ?? null;

        if (!$userId) {
            return ResponseHelper::json($response, false, 'Unauthorized', null, [])->withStatus(401);
        }

        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $perPage = (int)($request->getQueryParams()['per_page'] ?? 50);
        $result = $this->bookingService->getTutorBookings($userId, $page, $perPage);
        return ResponseHelper::json($response, true, 'Bookings retrieved', $result);
    }
}
