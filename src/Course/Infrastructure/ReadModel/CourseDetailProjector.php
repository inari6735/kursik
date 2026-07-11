<?php declare(strict_types=1);

namespace App\Course\Infrastructure\ReadModel;

use App\Course\Domain\CourseStatus;
use App\Course\Domain\Event\CourseCreated;
use App\Course\Domain\Event\CoursePublished;
use App\Course\Domain\Event\CourseRenamed;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Projects the course_detail read model. Runs on the sync transport, inside the
 * command's transaction: the detail page is read right after a redirect and must
 * never be stale. All writes are idempotent, so retries are safe.
 */
final readonly class CourseDetailProjector
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[AsMessageHandler(bus: 'messenger.bus.event', fromTransport: 'sync')]
    public function onCourseCreated(CourseCreated $event): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
                INSERT INTO course_detail (id, title, description, status, created_at, published_at)
                VALUES (:id, :title, :description, :status, :createdAt, NULL)
                ON CONFLICT (id) DO NOTHING
                SQL,
            [
                'id' => $event->courseId,
                'title' => $event->title,
                'description' => $event->description,
                'status' => CourseStatus::Draft->value,
                'createdAt' => $event->occurredAt,
            ],
            ['createdAt' => Types::DATETIMETZ_IMMUTABLE],
        );
    }

    #[AsMessageHandler(bus: 'messenger.bus.event', fromTransport: 'sync')]
    public function onCourseRenamed(CourseRenamed $event): void
    {
        $this->connection->update('course_detail', [
            'title' => $event->title,
            'description' => $event->description,
        ], ['id' => $event->courseId]);
    }

    #[AsMessageHandler(bus: 'messenger.bus.event', fromTransport: 'sync')]
    public function onCoursePublished(CoursePublished $event): void
    {
        $this->connection->update('course_detail', [
            'status' => CourseStatus::Published->value,
            'published_at' => $event->occurredAt,
        ], ['id' => $event->courseId], [
            'published_at' => Types::DATETIMETZ_IMMUTABLE,
        ]);
    }
}