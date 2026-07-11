<?php declare(strict_types=1);

namespace App\Shared\Domain;

/**
 * Base class for event-sourced aggregates.
 *
 * State changes only ever happen in apply<EventShortName>() methods, so replaying
 * history and handling a freshly recorded event share one code path.
 */
abstract class AggregateRoot
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    /** Number of events applied so far (persisted + newly recorded). */
    private int $version = 0;

    final protected function __construct()
    {
    }

    /**
     * Rebuilds the aggregate from its full event history, in version order.
     *
     * @param list<DomainEvent> $history
     */
    final public static function reconstitute(array $history): static
    {
        $aggregate = new static();

        foreach ($history as $event) {
            $aggregate->apply($event);
            ++$aggregate->version;
        }

        return $aggregate;
    }

    abstract public function aggregateId(): string;

    /**
     * Returns the events recorded since the last release and clears the buffer.
     * The caller (repository) is responsible for persisting and publishing them.
     *
     * @return list<DomainEvent>
     */
    final public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    final public function version(): int
    {
        return $this->version;
    }

    final protected function recordThat(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
        $this->apply($event);
        ++$this->version;
    }

    private function apply(DomainEvent $event): void
    {
        $method = 'apply'.(new \ReflectionClass($event))->getShortName();

        if (!method_exists($this, $method)) {
            throw new \LogicException(\sprintf('%s has no "%s()" method to apply "%s".', static::class, $method, $event::class));
        }

        $this->{$method}($event);
    }
}