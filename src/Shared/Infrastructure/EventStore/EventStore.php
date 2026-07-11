<?php declare(strict_types=1);

namespace App\Shared\Infrastructure\EventStore;

use App\Shared\Domain\DomainEvent;

interface EventStore
{
    /**
     * Appends events to the aggregate's stream, expecting the stream to currently
     * end at $expectedVersion (0 for a brand-new aggregate).
     *
     * @param list<DomainEvent> $events
     *
     * @throws ConcurrencyException when the stream was modified concurrently
     */
    public function append(string $aggregateId, string $aggregateType, int $expectedVersion, array $events): void;

    /**
     * @return list<DomainEvent> full history in version order; empty for an unknown aggregate
     */
    public function load(string $aggregateId): array;
}