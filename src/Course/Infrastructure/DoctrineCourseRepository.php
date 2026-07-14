<?php declare(strict_types=1);

namespace App\Course\Infrastructure;

use App\Course\Domain\Course;
use App\Course\Domain\CourseId;
use App\Course\Domain\CourseRepository;
use App\Course\Domain\Exception\CourseNotFound;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineCourseRepository implements CourseRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function get(CourseId $id): Course
    {
        return $this->entityManager->find(Course::class, Uuid::fromString($id->toString()))
            ?? throw CourseNotFound::withId($id);
    }

    public function save(Course $course): void
    {
        $this->entityManager->persist($course);
        $this->entityManager->flush();
    }
}
