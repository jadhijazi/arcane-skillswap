<?php
declare(strict_types=1);

namespace App\Models;

class Wallet
{
    public ?int $id = null;
    public int $user_id;
    public float $balance = 0.0;
    public string $currency = 'USD';
    public string $updated_at;

    public function __construct(array $data = [])
    {
        foreach ($data as $k => $v) {
            if (property_exists($this, $k)) {
                $this->{$k} = $v;
            }
        }
    }
}
