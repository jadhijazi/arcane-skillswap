<?php
declare(strict_types=1);

namespace App\Models;

class Review
{
    public ?int $id = null;
    public int $booking_id;
    public int $reviewer_id;
    public int $rating; // 1-5
    public ?string $comment = null;
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
