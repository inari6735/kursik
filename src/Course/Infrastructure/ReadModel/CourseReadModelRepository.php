<?php declare(strict_types=1);

namespace App\Course\Infrastructure\ReadModel;

use App\Course\Application\Query\CourseDetail;
use App\Course\Application\Query\CourseListItem;
use App\Course\Application\Query\CourseReadModel;
use Doctrine\DBAL\Connection;

/**
 * Read side over the courses table — plain DBAL, no entity hydration.
 */
final readonly class CourseReadModelRepository implements CourseReadModel
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function find(string $courseId): ?CourseDetail
    {
        $row = $this->connection->fetchAssociative(
            'SELECT id, title, description, status, created_at, published_at FROM courses WHERE id = :id',
            ['id' => $courseId],
        );

        if (false === $row) {
            return null;
        }

        return new CourseDetail(
            $row['id'],
            $row['title'],
            $row['description'],
            $row['status'],
            new \DateTimeImmutable($row['created_at']),
            null !== $row['published_at'] ? new \DateTimeImmutable($row['published_at']) : null,
        );
    }

    public function all(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, title, status, created_at FROM courses ORDER BY created_at DESC, id',
        );

        return array_map(
            static fn (array $row): CourseListItem => new CourseListItem(
                $row['id'],
                $row['title'],
                $row['status'],
                new \DateTimeImmutable($row['created_at']),
            ),
            $rows,
        );
    }
}
