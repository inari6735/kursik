<?php declare(strict_types=1);

namespace App\Course\Application\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.query')]
final readonly class FindCourseHandler
{
    public function __construct(
        private CourseReadModel $readModel,
    ) {
    }

    public function __invoke(FindCourse $query): ?CourseDetail
    {
        return $this->readModel->find($query->courseId);
    }
}