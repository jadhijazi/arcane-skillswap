<?php
declare(strict_types=1);

namespace App\Models;

class AvailabilitySlot
{
    public ?int $id = null;
    public int $user_id;
    public string $start_time;
    public string $end_time;
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
