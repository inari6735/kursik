<?php declare(strict_types=1);

namespace App\Course\Infrastructure;

use App\Course\Domain\Course;
use App\Course\Domain\CourseId;
use App\Course\Domain\CourseRepository;
use App\Course\Domain\Exception\CourseNotFound;
use App\Shared\Infrastructure\EventStore\EventStore;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class EventStoreCourseRepository implements CourseRepository
{
    private const string AGGREGATE_TYPE = 'course';

    public function __construct(
        private EventStore $eventStore,
        private MessageBusInterface $eventBus,
    ) {
    }

    public function get(CourseId $id): Course
    {
        $history = $this->eventStore->load($id->toString());

        if ([] === $history) {
            throw CourseNotFound::withId($id);
        }

        return Course::reconstitute($history);
    }

    public function save(Course $course): void
    {
        $events = $course->releaseEvents();

        if ([] === $events) {
            return;
        }

        $expectedVersion = $course->version() - \count($events);
        $this->eventStore->append($course->aggregateId(), self::AGGREGATE_TYPE, $expectedVersion, $events);

        foreach ($events as $event) {
            $this->eventBus->dispatch($event);
        }
    }
}