<?php declare(strict_types=1);

namespace App\Access\Application\Query;

final readonly class UserWithRoles
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $id,
        public string $email,
        public array $roles,
    ) {
    }
}
