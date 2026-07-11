<?php declare(strict_types=1);

namespace App\Course\Application\Command;

use App\Course\Domain\Course;
use App\Course\Domain\CourseId;
use App\Course\Domain\CourseRepository;
use App\Shared\Domain\Clock;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class CreateCourseHandler
{
    public function __construct(
        private CourseRepository $courses,
        private Clock $clock,
    ) {
    }

    public function __invoke(CreateCourse $command): void
    {
        $course = Course::create(
            CourseId::fromString($command->courseId),
            $command->title,
            $command->description,
            $this->clock->now(),
        );

        $this->courses->save($course);
    }
}