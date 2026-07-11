<?php declare(strict_types=1);

namespace App\Course\Application\Command;

use App\Course\Domain\CourseId;
use App\Course\Domain\CourseRepository;
use App\Shared\Domain\Clock;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class RenameCourseHandler
{
    public function __construct(
        private CourseRepository $courses,
        private Clock $clock,
    ) {
    }

    public function __invoke(RenameCourse $command): void
    {
        $course = $this->courses->get(CourseId::fromString($command->courseId));
        $course->rename($command->title, $command->description, $this->clock->now());
        $this->courses->save($course);
    }
}