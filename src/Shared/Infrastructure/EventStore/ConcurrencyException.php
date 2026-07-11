<?php declare(strict_types=1);

namespace App\Shared\Infrastructure\EventStore;

final class ConcurrencyException extends \RuntimeException
{
    public static function forAggregate(string $aggregateId, int $expectedVersion, ?\Throwable $previous = null): self
    {
        return new self(
            \sprintf('Aggregate "%s" was modified concurrently (expected version %d).', $aggregateId, $expectedVersion),
            0,
            $previous,
        );
    }
}