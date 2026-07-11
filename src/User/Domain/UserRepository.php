<?php declare(strict_types=1);

namespace App\User\Domain;

interface UserRepository
{
    public function byEmail(string $email): ?User;

    public function emailExists(string $email): bool;

    public function add(User $user): void;
}