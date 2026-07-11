<?php declare(strict_types=1);

namespace App\Shared\Infrastructure\EventStore;

use App\Shared\Domain\DomainEvent;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Types\Types;

final class DbalEventStore implements EventStore
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EventTypeRegistry $eventTypes,
        private readonly Upcaster $upcaster,
    ) {
    }

    public function append(string $aggregateId, string $aggregateType, int $expectedVersion, array $events): void
    {
        $version = $expectedVersion;

        foreach ($events as $event) {
            try {
                $this->connection->insert('event_store', [
                    'aggregate_id' => $aggregateId,
                    'aggregate_type' => $aggregateType,
                    'version' => ++$version,
                    'event_type' => $this->eventTypes->typeFor($event::class),
                    'payload' => json_encode($event->toPayload(), \JSON_THROW_ON_ERROR),
                    'occurred_at' => $event->occurredAt(),
                ], [
                    'occurred_at' => Types::DATETIMETZ_IMMUTABLE,
                ]);
            } catch (UniqueConstraintViolationException $exception) {
                throw ConcurrencyException::forAggregate($aggregateId, $expectedVersion, $exception);
            }
        }
    }

    public function load(string $aggregateId): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT event_type, payload FROM event_store WHERE aggregate_id = ? ORDER BY version',
            [$aggregateId],
        );

        return array_map(function (array $row): DomainEvent {
            $payload = json_decode($row['payload'], true, flags: \JSON_THROW_ON_ERROR);
            $payload = $this->upcaster->upcast($row['event_type'], $payload);

            return $this->eventTypes->classFor($row['event_type'])::fromPayload($payload);
        }, $rows);
    }
}