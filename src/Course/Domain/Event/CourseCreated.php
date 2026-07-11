<?php declare(strict_types=1);

namespace App\Course\Domain\Event;

use App\Shared\Domain\DomainEvent;

final readonly class CourseCreated implements DomainEvent
{
    private const string DATE_FORMAT = DATE_W3C;

    public function __construct(
        public string $courseId,
        public string $title,
        public string $description,
        public \DateTimeImmutable $occurredAt,
    ) {
    }

    public static function eventType(): string
    {
        return 'course.created.v1';
    }

    public function aggregateId(): string
    {
        return $this->courseId;
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function toPayload(): array
    {
        return [
            'courseId' => $this->courseId,
            'title' => $this->title,
            'description' => $this->description,
            'occurredAt' => $this->occurredAt->format(self::DATE_FORMAT),
        ];
    }

    public static function fromPayload(array $payload): static
    {
        return new self(
            $payload['courseId'],
            $payload['title'],
            $payload['description'],
            new \DateTimeImmutable($payload['occurredAt']),
        );
    }
}
