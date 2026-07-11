<?php declare(strict_types=1);

namespace App\Shared\Infrastructure\EventStore;

/**
 * Hook between raw stored rows and DomainEvent::fromPayload(): migrates old
 * payload shapes when an event's schema evolves, without rewriting the stream.
 */
interface Upcaster
{
    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed> the payload in the shape the current event class expects
     */
    public function upcast(string $eventType, array $payload): array;
}