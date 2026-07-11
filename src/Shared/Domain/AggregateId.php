<?php declare(strict_types=1);

namespace App\Shared\Domain;

use Symfony\Component\Uid\Uuid;

/**
 * Base class for aggregate identifiers: an immutable UUIDv7 value object.
 */
abstract readonly class AggregateId implements \Stringable
{
    final private function __construct(private string $value)
    {
    }

    final public static function generate(): static
    {
        return new static(Uuid::v7()->toRfc4122());
    }

    final public static function fromString(string $value): static
    {
        if (!Uuid::isValid($value)) {
            throw new \InvalidArgumentException(\sprintf('"%s" is not a valid %s.', $value, static::class));
        }

        return new static($value);
    }

    final public function toString(): string
    {
        return $this->value;
    }

    final public function equals(self $other): bool
    {
        return $other::class === static::class && $other->value === $this->value;
    }

    final public function __toString(): string
    {
        return $this->value;
    }
}