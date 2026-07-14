<?php declare(strict_types=1);

namespace App\Access\Domain;

use Symfony\Component\Uid\Uuid;

interface RoleRepository
{
    /**
     * @return list<Role>
     */
    public function all(): array;

    public function byId(Uuid $id): ?Role;

    public function byName(string $name): ?Role;

    /**
     * @param list<string> $names
     *
     * @return list<Role>
     */
    public function byNames(array $names): array;

    public function nameExists(string $name): bool;

    public function add(Role $role): void;

    public function save(Role $role): void;

    public function remove(Role $role): void;
}
