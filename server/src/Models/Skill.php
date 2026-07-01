<?php
declare(strict_types=1);

namespace App\Models;

class Skill
{
    public ?int $id = null;
    public string $name;
    public ?string $category = null;
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
