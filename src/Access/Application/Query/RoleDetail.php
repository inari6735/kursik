<?php declare(strict_types=1);

namespace App\Access\Application\Query;

final readonly class RoleDetail
{
    /**
     * @param list<string> $permissions
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $permissions,
    ) {
    }
}
