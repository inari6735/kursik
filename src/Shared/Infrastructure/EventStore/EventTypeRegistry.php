<?php declare(strict_types=1);

namespace App\Shared\Infrastructure\EventStore;

use App\Shared\Domain\DomainEvent;

/**
 * Maps stable event-type names ('course.created.v1') to event classes and back,
 * so that persisted streams survive class renames and moves.
 */
final class EventTypeRegistry
{
    /** @var array<string, class-string<DomainEvent>> */
    private array $classByType = [];

    /** @var array<class-string<DomainEvent>, string> */
    private array $typeByClass = [];

    /**
     * @param iterable<class-string<DomainEvent>> $eventClasses
     */
    public function __construct(iterable $eventClasses = [])
    {
        foreach ($eventClasses as $eventClass) {
            $this->register($eventClass);
        }
    }

    /**
     * @param class-string<DomainEvent> $eventClass
     */
    public function register(string $eventClass): void
    {
        if (!is_a($eventClass, DomainEvent::class, true)) {
            throw new \InvalidArgumentException(\sprintf('"%s" does not implement "%s".', $eventClass, DomainEvent::class));
        }

        $eventType = $eventClass::eventType();

        if (isset($this->classByType[$eventType]) && $this->classByType[$eventType] !== $eventClass) {
            throw new \InvalidArgumentException(\sprintf('Event type "%s" is already registered for "%s".', $eventType, $this->classByType[$eventType]));
        }

        $this->classByType[$eventType] = $eventClass;
        $this->typeByClass[$eventClass] = $eventType;
    }

    /**
     * @return class-string<DomainEvent>
     */
    public function classFor(string $eventType): string
    {
        return $this->classByType[$eventType]
            ?? throw new \InvalidArgumentException(\sprintf('Unknown event type "%s" — register its class in the EventTypeRegistry.', $eventType));
    }

    /**
     * @param class-string<DomainEvent> $eventClass
     */
    public function typeFor(string $eventClass): string
    {
        return $this->typeByClass[$eventClass]
            ?? throw new \InvalidArgumentException(\sprintf('Event class "%s" is not registered in the EventTypeRegistry.', $eventClass));
    }
}