<?php

declare(strict_types=1);

use App\Config\Database;
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
use App\Helpers\JwtHelper;
use App\Repositories\UserRepository;
use App\Repositories\SkillRepository;
use App\Repositories\UserSkillRepository;
use App\Repositories\TutorRepository;
use App\Repositories\AvailabilitySlotRepository;
use App\Repositories\BookingRepository;
use App\Repositories\WalletRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\MessageRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\AuditLogRepository;
use App\Services\AuthService;
use App\Services\SkillService;
use App\Services\UserSkillService;
use App\Services\TutorDiscoveryService;
use App\Services\AvailabilitySlotService;
use App\Services\BookingService;
use App\Services\WalletService;
use App\Services\ReviewService;
use App\Services\MessageService;
use App\Services\NotificationService;
use App\Services\UserService;
use App\Services\AdminService;
use PDO;
use Psr\Container\ContainerInterface;

return [
        'settings' => [
            'displayErrorDetails' => (getenv('APP_ENV') === 'development'),
        ],

        PDO::class => function () {
            $db = new Database([
                'host' => getenv('DB_HOST') ?: 'localhost',
                'port' => (int)(getenv('DB_PORT') ?: '3306'),
                'database' => getenv('DB_DATABASE') ?: 'skillswap',
                'username' => getenv('DB_USERNAME') ?: 'root',
                'password' => getenv('DB_PASSWORD') ?: '',
            ]);
            return $db->getPdo();
        },

        JwtHelper::class => function () {
            return new JwtHelper([
                'secret' => getenv('JWT_SECRET') ?: 'dev-secret-key',
                'issuer' => getenv('JWT_ISSUER') ?: 'skillswap.local',
                'audience' => getenv('JWT_AUDIENCE') ?: 'skillswap.local',
            ]);
        },

        UserRepository::class => fn (ContainerInterface $c) => new UserRepository($c->get(PDO::class)),
        SkillRepository::class => fn (ContainerInterface $c) => new SkillRepository($c->get(PDO::class)),
        UserSkillRepository::class => fn (ContainerInterface $c) => new UserSkillRepository($c->get(PDO::class)),
        TutorRepository::class => fn (ContainerInterface $c) => new TutorRepository($c->get(PDO::class)),
        AvailabilitySlotRepository::class => fn (ContainerInterface $c) => new AvailabilitySlotRepository($c->get(PDO::class)),
        BookingRepository::class => fn (ContainerInterface $c) => new BookingRepository($c->get(PDO::class)),
        WalletRepository::class => fn (ContainerInterface $c) => new WalletRepository($c->get(PDO::class)),
        ReviewRepository::class => fn (ContainerInterface $c) => new ReviewRepository($c->get(PDO::class)),
        MessageRepository::class => fn (ContainerInterface $c) => new MessageRepository($c->get(PDO::class)),
        NotificationRepository::class => fn (ContainerInterface $c) => new NotificationRepository($c->get(PDO::class)),
        AuditLogRepository::class => fn (ContainerInterface $c) => new AuditLogRepository($c->get(PDO::class)),

        AuthService::class => function (ContainerInterface $c) {
            return new AuthService(
                $c->get(UserRepository::class),
                $c->get(JwtHelper::class),
                $c->get(PDO::class),
                (int)(getenv('JWT_ACCESS_TTL') ?: '900'),
                (int)(getenv('JWT_REFRESH_TTL') ?: '604800'),
            );
        },
        SkillService::class => fn (ContainerInterface $c) => new SkillService($c->get(SkillRepository::class)),
        UserSkillService::class => fn (ContainerInterface $c) => new UserSkillService(
            $c->get(UserSkillRepository::class),
            $c->get(UserRepository::class)
        ),
        TutorDiscoveryService::class => fn (ContainerInterface $c) => new TutorDiscoveryService($c->get(TutorRepository::class)),
        AvailabilitySlotService::class => fn (ContainerInterface $c) => new AvailabilitySlotService($c->get(AvailabilitySlotRepository::class)),
        WalletService::class => function (ContainerInterface $c) {
            $commission = (float)(getenv('PLATFORM_COMMISSION') ?: '0.10');
            return new WalletService($c->get(WalletRepository::class), $commission);
        },
        NotificationService::class => fn (ContainerInterface $c) => new NotificationService($c->get(NotificationRepository::class)),
        BookingService::class => fn (ContainerInterface $c) => new BookingService(
            $c->get(BookingRepository::class),
            $c->get(UserSkillRepository::class),
            $c->get(WalletService::class),
            $c->get(NotificationService::class)
        ),
        ReviewService::class => fn (ContainerInterface $c) => new ReviewService(
            $c->get(ReviewRepository::class),
            $c->get(BookingRepository::class),
            $c->get(NotificationService::class)
        ),
        MessageService::class => fn (ContainerInterface $c) => new MessageService(
            $c->get(MessageRepository::class),
            $c->get(NotificationService::class)
        ),
        UserService::class => fn (ContainerInterface $c) => new UserService(
            $c->get(UserRepository::class),
            $c->get(ReviewRepository::class),
            $c->get(WalletRepository::class)
        ),
        AdminService::class => fn (ContainerInterface $c) => new AdminService(
            $c->get(UserRepository::class),
            $c->get(BookingRepository::class),
            $c->get(ReviewRepository::class),
            $c->get(SkillRepository::class),
            $c->get(WalletRepository::class),
            $c->get(AuditLogRepository::class)
        ),

        AuthController::class => fn (ContainerInterface $c) => new AuthController($c->get(AuthService::class)),
        SkillController::class => fn (ContainerInterface $c) => new SkillController($c->get(SkillService::class)),
        UserSkillController::class => fn (ContainerInterface $c) => new UserSkillController($c->get(UserSkillService::class)),
        TutorDiscoveryController::class => fn (ContainerInterface $c) => new TutorDiscoveryController($c->get(TutorDiscoveryService::class)),
        AvailabilitySlotController::class => fn (ContainerInterface $c) => new AvailabilitySlotController($c->get(AvailabilitySlotService::class)),
        BookingController::class => fn (ContainerInterface $c) => new BookingController($c->get(BookingService::class)),
        WalletController::class => fn (ContainerInterface $c) => new WalletController($c->get(WalletService::class)),
        ReviewController::class => fn (ContainerInterface $c) => new ReviewController($c->get(ReviewService::class)),
        MessageController::class => fn (ContainerInterface $c) => new MessageController($c->get(MessageService::class)),
        NotificationController::class => fn (ContainerInterface $c) => new NotificationController($c->get(NotificationService::class)),
        UserController::class => fn (ContainerInterface $c) => new UserController($c->get(UserService::class)),
        AdminController::class => fn (ContainerInterface $c) => new AdminController($c->get(AdminService::class)),
];