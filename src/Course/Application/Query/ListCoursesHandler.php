<?php declare(strict_types=1);

namespace App\Course\Application\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.query')]
final readonly class ListCoursesHandler
{
    public function __construct(
        private CourseReadModel $readModel,
    ) {
    }

    /**
     * @return list<CourseListItem>
     */
    public function __invoke(ListCourses $query): array
    {
        return $this->readModel->all();
    }
}