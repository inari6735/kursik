<?php declare(strict_types=1);

namespace App\Access\Infrastructure;

use App\Access\Application\UserRoleAssignments;
use Doctrine\DBAL\Connection;

/**
 * Operates directly on the users.roles JSON column via DBAL.
 * jsonb_exists() is used instead of the `?` operator, which would clash
 * with DBAL parameter placeholders.
 */
final readonly class DbalUserRoleAssignments implements UserRoleAssignments
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function rolesOf(string $userId): ?array
    {
        $roles = $this->connection->fetchOne('SELECT roles FROM users WHERE id = :id', ['id' => $userId]);

        if (false === $roles) {
            return null;
        }

        return array_values(json_decode($roles, true, flags: \JSON_THROW_ON_ERROR));
    }

    public function assign(string $userId, array $roleNames): void
    {
        $this->connection->update('users', [
            'roles' => json_encode(array_values($roleNames), \JSON_THROW_ON_ERROR),
        ], ['id' => $userId]);
    }

    public function countUsersWithRole(string $roleName, ?string $excludingUserId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE jsonb_exists(roles::jsonb, :name)';
        $params = ['name' => $roleName];

        if (null !== $excludingUserId) {
            $sql .= ' AND id != :excluded';
            $params['excluded'] = $excludingUserId;
        }

        return (int) $this->connection->fetchOne($sql, $params);
    }

    public function removeRoleFromAllUsers(string $roleName): void
    {
        $this->connection->executeStatement(
            'UPDATE users SET roles = (roles::jsonb - :name)::json WHERE jsonb_exists(roles::jsonb, :name)',
            ['name' => $roleName],
        );
    }
}
