<?php declare(strict_types=1);

namespace App\Access\Application;

/**
 * Port to the User context's role assignments (the users.roles JSON column).
 * Keeps Access from depending on the User context directly.
 */
interface UserRoleAssignments
{
    /**
     * @return list<string>|null role names, or null when the user does not exist
     */
    public function rolesOf(string $userId): ?array;

    /**
     * @param list<string> $roleNames
     */
    public function assign(string $userId, array $roleNames): void;

    public function countUsersWithRole(string $roleName, ?string $excludingUserId = null): int;

    public function removeRoleFromAllUsers(string $roleName): void;
}
