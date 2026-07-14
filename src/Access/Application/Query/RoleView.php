<?php declare(strict_types=1);

namespace App\Access\Application\Query;

final readonly class RoleView
{
    public function __construct(
        public string $id,
        public string $name,
        public int $permissionCount,
        public int $userCount,
    ) {
    }
}
