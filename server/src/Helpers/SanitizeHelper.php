<?php
declare(strict_types=1);

namespace App\Helpers;

class SanitizeHelper
{
    public static function string(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function email(string $value): string
    {
        return strtolower(trim($value));
    }

    /** @param array<string, mixed> $data */
    public static function sanitizeArray(array $data, array $stringKeys): array
    {
        foreach ($stringKeys as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = self::string($data[$key]);
            }
        }

        return $data;
    }
}
