<?php
declare(strict_types=1);

use Slim\App;
use App\Controllers\AuthController;
use App\Controllers\SkillController;
use App\Controllers\UserSkillController;
use App\Controllers\TutorDiscoveryController;
use App\Controllers\AvailabilitySlotController;
use App\Controllers\BookingController;
use App\Controllers\WalletController;
use App\Controllers\ReviewController;
use App\Controllers\MessageController;
use App\Controllers\NotificationController;
use App\Controllers\UserController;
use App\Controllers\AdminController;
use App\Middleware\CorsMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

return function (App $app) {
    $container = $app->getContainer();
    $jwt = $container->get(\App\Helpers\JwtHelper::class);

    $auth = new AuthMiddleware($jwt);
    $admin = new RoleMiddleware(['Admin']);

    $app->add(new CorsMiddleware());
    $app->add(new RateLimitMiddleware(200, 60));

    // ==================== AUTH ENDPOINTS ====================
    $app->post('/api/auth/register', AuthController::class . ':register');
    $app->post('/api/auth/login', AuthController::class . ':login');
    $app->post('/api/auth/refresh', AuthController::class . ':refresh');
    $app->post('/api/auth/logout', AuthController::class . ':logout');
    $app->post('/api/auth/forgot-password', AuthController::class . ':forgotPassword');
    $app->post('/api/auth/reset-password', AuthController::class . ':resetPassword');

    // ==================== USER ENDPOINTS ====================
    $app->get('/api/users/me', UserController::class . ':getCurrentUser')->add($auth);
    $app->get('/api/users/{id}', UserController::class . ':getProfile');
    $app->patch('/api/users/me', UserController::class . ':update')->add($auth);
    $app->post('/api/users/change-password', UserController::class . ':changePassword')->add($auth);

    // ==================== SKILL ENDPOINTS ====================
    $app->get('/api/skills', SkillController::class . ':list');
    $app->get('/api/skills/search', SkillController::class . ':search');
    $app->get('/api/skills/trending', SkillController::class . ':trending');
    $app->get('/api/skills/filter', SkillController::class . ':filterByCategory');
    $app->get('/api/skills/{id}', SkillController::class . ':get');
    $app->post('/api/skills', SkillController::class . ':create')->add($admin)->add($auth);
    $app->patch('/api/skills/{id}', SkillController::class . ':update')->add($admin)->add($auth);
    $app->delete('/api/skills/{id}', SkillController::class . ':delete')->add($admin)->add($auth);

    // ==================== USER SKILLS ENDPOINTS ====================
    $app->post('/api/user-skills', UserSkillController::class . ':create')->add($auth);
    $app->get('/api/user-skills/{id}', UserSkillController::class . ':get');
    $app->get('/api/users/{user_id}/skills', UserSkillController::class . ':getByUser');
    $app->patch('/api/user-skills/{id}', UserSkillController::class . ':update')->add($auth);
    $app->delete('/api/user-skills/{id}', UserSkillController::class . ':delete')->add($auth);

    // ==================== TUTOR DISCOVERY ENDPOINTS ====================
    $app->get('/api/tutors/search', TutorDiscoveryController::class . ':search');

    // ==================== AVAILABILITY SLOTS ENDPOINTS ====================
    $app->post('/api/availability-slots', AvailabilitySlotController::class . ':create')->add($auth);
    $app->get('/api/availability-slots/{id}', AvailabilitySlotController::class . ':get');
    $app->get('/api/users/{user_id}/availability-slots', AvailabilitySlotController::class . ':getByUser');
    $app->patch('/api/availability-slots/{id}', AvailabilitySlotController::class . ':update')->add($auth);
    $app->delete('/api/availability-slots/{id}', AvailabilitySlotController::class . ':delete')->add($auth);

    // ==================== BOOKING ENDPOINTS ====================
    $app->post('/api/bookings', BookingController::class . ':create')->add($auth);
    $app->get('/api/bookings/learner', BookingController::class . ':getLearnerBookings')->add($auth);
    $app->get('/api/bookings/tutor', BookingController::class . ':getTutorBookings')->add($auth);
    $app->get('/api/bookings/{id}', BookingController::class . ':get');
    $app->patch('/api/bookings/{id}/accept', BookingController::class . ':accept')->add($auth);
    $app->patch('/api/bookings/{id}/decline', BookingController::class . ':decline')->add($auth);
    $app->patch('/api/bookings/{id}/confirm', BookingController::class . ':confirm')->add($auth);
    $app->patch('/api/bookings/{id}/complete', BookingController::class . ':complete')->add($auth);
    $app->patch('/api/bookings/{id}/cancel', BookingController::class . ':cancel')->add($auth);

    // ==================== WALLET ENDPOINTS ====================
    $app->get('/api/wallet', WalletController::class . ':getBalance')->add($auth);
    $app->get('/api/wallet/transactions', WalletController::class . ':getTransactions')->add($auth);
    $app->get('/api/wallet/report', WalletController::class . ':getReport')->add($auth);

    // ==================== REVIEW ENDPOINTS ====================
    $app->post('/api/reviews', ReviewController::class . ':create')->add($auth);
    $app->get('/api/reviews/{id}', ReviewController::class . ':get');
    $app->get('/api/tutors/{tutor_id}/reviews', ReviewController::class . ':getTutorReviews');

    // ==================== MESSAGE ENDPOINTS ====================
    $app->post('/api/messages', MessageController::class . ':send')->add($auth);
    $app->get('/api/messages/unread-count', MessageController::class . ':getUnreadCount')->add($auth);
    $app->get('/api/messages/{id}', MessageController::class . ':get');
    $app->get('/api/conversations/{other_user_id}', MessageController::class . ':getConversation')->add($auth);
    $app->patch('/api/messages/{id}/read', MessageController::class . ':markAsRead')->add($auth);
    $app->patch('/api/conversations/{sender_id}/read', MessageController::class . ':markConversationAsRead')->add($auth);

    // ==================== NOTIFICATION ENDPOINTS ====================
    $app->get('/api/notifications', NotificationController::class . ':list')->add($auth);
    $app->get('/api/notifications/unread-count', NotificationController::class . ':getUnreadCount')->add($auth);
    $app->get('/api/notifications/{id}', NotificationController::class . ':get')->add($auth);
    $app->patch('/api/notifications/{id}/read', NotificationController::class . ':markAsRead')->add($auth);
    $app->patch('/api/notifications/read-all', NotificationController::class . ':markAllAsRead')->add($auth);

    // ==================== ADMIN ENDPOINTS ====================
    $app->get('/api/admin/dashboard', AdminController::class . ':dashboard')->add($admin)->add($auth);
    $app->get('/api/admin/users', AdminController::class . ':listUsers')->add($admin)->add($auth);
    $app->get('/api/admin/tutors', AdminController::class . ':listTutors')->add($admin)->add($auth);
    $app->patch('/api/admin/users/{id}/deactivate', AdminController::class . ':deactivateUser')->add($admin)->add($auth);
    $app->patch('/api/admin/users/{id}/activate', AdminController::class . ':activateUser')->add($admin)->add($auth);
    $app->get('/api/admin/bookings', AdminController::class . ':listBookings')->add($admin)->add($auth);
    $app->delete('/api/admin/reviews/{id}', AdminController::class . ':deleteReview')->add($admin)->add($auth);
    $app->get('/api/admin/wallet-report', AdminController::class . ':walletReport')->add($admin)->add($auth);
    $app->get('/api/admin/audit-logs', AdminController::class . ':auditLogs')->add($admin)->add($auth);
};
