<?php declare(strict_types=1);

namespace App\Access\Application\Query;

/**
 * Port to the admin-panel read side; implemented in Infrastructure.
 */
interface AccessReadModel
{
    /**
     * @return list<UserWithRoles>
     */
    public function users(): array;

    public function userById(string $userId): ?UserWithRoles;

    /**
     * @return list<RoleView>
     */
    public function roles(): array;

    public function roleById(string $roleId): ?RoleDetail;
}
