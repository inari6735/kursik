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
 * Projects the course_list read model. Runs on the async (Doctrine) transport,
 * processed by the messenger:consume worker — the list may lag a moment behind.
 * All writes are idempotent, so retries are safe.
 */
final readonly class CourseListProjector
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    #[AsMessageHandler(bus: 'messenger.bus.event', fromTransport: 'async')]
    public function onCourseCreated(CourseCreated $event): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
                INSERT INTO course_list (id, title, status, created_at)
                VALUES (:id, :title, :status, :createdAt)
                ON CONFLICT (id) DO NOTHING
                SQL,
            [
                'id' => $event->courseId,
                'title' => $event->title,
                'status' => CourseStatus::Draft->value,
                'createdAt' => $event->occurredAt,
            ],
            ['createdAt' => Types::DATETIMETZ_IMMUTABLE],
        );
    }

    #[AsMessageHandler(bus: 'messenger.bus.event', fromTransport: 'async')]
    public function onCourseRenamed(CourseRenamed $event): void
    {
        $this->connection->update('course_list', [
            'title' => $event->title,
        ], ['id' => $event->courseId]);
    }

    #[AsMessageHandler(bus: 'messenger.bus.event', fromTransport: 'async')]
    public function onCoursePublished(CoursePublished $event): void
    {
        $this->connection->update('course_list', [
            'status' => CourseStatus::Published->value,
        ], ['id' => $event->courseId]);
    }
}