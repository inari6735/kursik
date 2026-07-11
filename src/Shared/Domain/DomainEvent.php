<?php declare(strict_types=1);

namespace App\Shared\Domain;

interface DomainEvent
{
    public static function eventType(): string;

    public function aggregateId(): string;

    public function occurredAt(): \DateTimeImmutable;

    public function toPayload(): array;

    public static function fromPayload(array $payload): static;
}
