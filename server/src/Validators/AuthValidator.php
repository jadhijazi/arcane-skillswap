<?php
declare(strict_types=1);

namespace App\Validators;

class AuthValidator extends Validator
{
    /** @param array<string, mixed> $data */
    public function validateRegister(array $data): self
    {
        return $this
            ->required($data, ['email', 'password', 'first_name', 'last_name'])
            ->email($data)
            ->minLength($data, 'password', 6);
    }

    /** @param array<string, mixed> $data */
    public function validateLogin(array $data): self
    {
        return $this->required($data, ['email', 'password'])->email($data);
    }

    /** @param array<string, mixed> $data */
    public function validateForgotPassword(array $data): self
    {
        return $this->required($data, ['email'])->email($data);
    }

    /** @param array<string, mixed> $data */
    public function validateChangePassword(array $data): self
    {
        return $this
            ->required($data, ['old_password', 'new_password'])
            ->minLength($data, 'new_password', 6);
    }
}
