<?php
declare(strict_types=1);

namespace App\Models;

class Booking
{
    public ?int $id = null;
    public int $learner_id;
    public int $tutor_id;
    public int $user_skill_id;
    public string $start_time;
    public string $end_time;
    public string $status = 'pending'; // pending, accepted, declined, confirmed, completed, cancelled
    public float $amount = 0.0;
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
