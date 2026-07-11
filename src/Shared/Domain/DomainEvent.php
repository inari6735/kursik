<?php declare(strict_types=1);

namespace App\Shared\Domain;

interface DomainEvent
{
    /**
     * Stable, versioned name persisted in the event store, e.g. 'course.created.v1'.
     * Never derive it from the class name — renaming a class must not corrupt streams.
     */
    public static function eventType(): string;

    public function aggregateId(): string;

    public function occurredAt(): \DateTimeImmutable;

    /**
     * @return array<string, mixed> payload from which fromPayload() can fully rebuild the event
     */
    public function toPayload(): array;

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromPayload(array $payload): static;
}