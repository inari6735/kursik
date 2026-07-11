<?php declare(strict_types=1);

namespace App\Shared\Infrastructure\EventStore;

final class NoOpUpcaster implements Upcaster
{
    public function upcast(string $eventType, array $payload): array
    {
        return $payload;
    }
}