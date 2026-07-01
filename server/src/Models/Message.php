<?php
declare(strict_types=1);

namespace App\Models;

class Message
{
    public ?int $id = null;
    public int $sender_id;
    public int $recipient_id;
    public string $content;
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
