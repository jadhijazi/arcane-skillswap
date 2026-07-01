<?php
declare(strict_types=1);

namespace App\Models;

class UserSkill
{
    public ?int $id = null;
    public int $user_id;
    public int $skill_id;
    public float $hourly_rate = 0.0;
    public ?string $experience_level = null;
    public ?string $description = null;
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
