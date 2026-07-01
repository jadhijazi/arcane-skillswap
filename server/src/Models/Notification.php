<?php
declare(strict_types=1);

namespace App\Models;

class Notification
{
    public ?int $id = null;
    public int $user_id;
    public ?string $type = null; // booking, review, message, system
    public ?array $data = null;
    public bool $is_read = false;
    public string $created_at;

    public function __construct(array $data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->{$k} = $v;
            }
        }
    }
}
