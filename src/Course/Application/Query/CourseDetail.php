<?php declare(strict_types=1);

namespace App\Course\Application\Query;

final readonly class CourseDetail
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $status,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $publishedAt,
    ) {
    }
}