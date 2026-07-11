<?php declare(strict_types=1);

namespace App\Course\Application\Query;

final readonly class CourseListItem
{
    public function __construct(
        public string $id,
        public string $title,
        public string $status,
        public \DateTimeImmutable $createdAt,
    ) {
    }
}