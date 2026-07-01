<?php
declare(strict_types=1);

namespace App\Models;

class WalletTransaction
{
    public ?int $id = null;
    public int $wallet_id;
    public float $amount;
    public string $type; // credit, debit
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
