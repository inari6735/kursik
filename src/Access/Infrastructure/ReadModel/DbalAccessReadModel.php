<?php declare(strict_types=1);

namespace App\Access\Infrastructure\ReadModel;

use App\Access\Application\Query\AccessReadModel;
use App\Access\Application\Query\RoleDetail;
use App\Access\Application\Query\RoleView;
use App\Access\Application\Query\UserWithRoles;
use Doctrine\DBAL\Connection;

final readonly class DbalAccessReadModel implements AccessReadModel
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function users(): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, email, roles FROM users ORDER BY email');

        return array_map(self::userFromRow(...), $rows);
    }

    public function userById(string $userId): ?UserWithRoles
    {
        $row = $this->connection->fetchAssociative('SELECT id, email, roles FROM users WHERE id = :id', ['id' => $userId]);

        return false === $row ? null : self::userFromRow($row);
    }

    public function roles(): array
    {
        $rows = $this->connection->fetchAllAssociative(<<<'SQL'
            SELECT r.id, r.name, r.permissions,
                   (SELECT COUNT(*) FROM users u WHERE jsonb_exists(u.roles::jsonb, r.name)) AS user_count
            FROM roles r
            ORDER BY r.name
            SQL);

        return array_map(
            static fn (array $row): RoleView => new RoleView(
                $row['id'],
                $row['name'],
                \count(json_decode($row['permissions'], true, flags: \JSON_THROW_ON_ERROR)),
                (int) $row['user_count'],
            ),
            $rows,
        );
    }

    public function roleById(string $roleId): ?RoleDetail
    {
        $row = $this->connection->fetchAssociative('SELECT id, name, permissions FROM roles WHERE id = :id', ['id' => $roleId]);

        if (false === $row) {
            return null;
        }

        return new RoleDetail(
            $row['id'],
            $row['name'],
            array_values(json_decode($row['permissions'], true, flags: \JSON_THROW_ON_ERROR)),
        );
    }

    /**
     * @param array{id: string, email: string, roles: string} $row
     */
    private static function userFromRow(array $row): UserWithRoles
    {
        return new UserWithRoles(
            $row['id'],
            $row['email'],
            array_values(json_decode($row['roles'], true, flags: \JSON_THROW_ON_ERROR)),
        );
    }
}
