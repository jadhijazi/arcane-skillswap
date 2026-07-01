<?php
declare(strict_types=1);

namespace App\Helpers;

class PaginationHelper
{
    public static function resolve(array $queryParams, int $defaultPerPage = 20, int $maxPerPage = 100): array
    {
        $page = max(1, (int)($queryParams['page'] ?? 1));
        $perPage = min($maxPerPage, max(1, (int)($queryParams['per_page'] ?? $defaultPerPage)));
        $offset = ($page - 1) * $perPage;

        return ['page' => $page, 'per_page' => $perPage, 'offset' => $offset];
    }

    public static function meta(int $total, int $page, int $perPage): array
    {
        return [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $perPage > 0 ? (int)ceil($total / $perPage) : 0,
        ];
    }
}
