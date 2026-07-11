<?php declare(strict_types=1);

namespace App\Course\Domain;

use App\Course\Domain\Exception\CourseNotFound;

interface CourseRepository
{
    /**
     * @throws CourseNotFound
     */
    public function get(CourseId $id): Course;

    public function save(Course $course): void;
}