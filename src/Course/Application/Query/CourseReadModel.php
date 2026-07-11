<?php declare(strict_types=1);

namespace App\Course\Application\Query;

/**
 * Port to the denormalized read models; implemented in Infrastructure.
 * Queries never touch the event store or the aggregate.
 */
interface CourseReadModel
{
    public function find(string $courseId): ?CourseDetail;

    /**
     * @return list<CourseListItem>
     */
    public function all(): array;
}