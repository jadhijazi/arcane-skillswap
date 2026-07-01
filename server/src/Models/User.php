<?php
declare(strict_types=1);

namespace App\Models;

class User
{
    public ?int $id = null;
    public string $email;
    public string $password_hash;
    public string $first_name;
    public string $last_name;
    public ?string $bio = null;
    public ?string $profile_photo = null;
    public ?string $faculty = null;
    public ?string $year = null;
    public bool $is_active = true;
    public ?string $password_reset_token = null;
    public ?string $password_reset_expires = null;
    public string $created_at;
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
