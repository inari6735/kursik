<?php declare(strict_types=1);

namespace App\Course\Domain;

use App\Course\Domain\Event\CourseCreated;
use App\Course\Domain\Event\CoursePublished;
use App\Course\Domain\Event\CourseRenamed;
use App\Course\Domain\Exception\CourseAlreadyPublished;
use App\Course\Domain\Exception\InvalidCourseTitle;
use App\Shared\Domain\AggregateRoot;

final class Course extends AggregateRoot
{
    private CourseId $id;
    private string $title;
    private string $description;
    private CourseStatus $status;

    public static function create(CourseId $id, string $title, string $description, \DateTimeImmutable $now): self
    {
        $title = self::validatedTitle($title);

        $course = new self();
        $course->recordThat(new CourseCreated($id->toString(), $title, $description, $now));

        return $course;
    }

    public function rename(string $title, string $description, \DateTimeImmutable $now): void
    {
        if (CourseStatus::Published === $this->status) {
            throw CourseAlreadyPublished::withId($this->id);
        }

        $title = self::validatedTitle($title);

        if ($title === $this->title && $description === $this->description) {
            return;
        }

        $this->recordThat(new CourseRenamed($this->id->toString(), $title, $description, $now));
    }

    public function publish(\DateTimeImmutable $now): void
    {
        if (CourseStatus::Published === $this->status) {
            throw CourseAlreadyPublished::withId($this->id);
        }

        $this->recordThat(new CoursePublished($this->id->toString(), $now));
    }

    public function id(): CourseId
    {
        return $this->id;
    }

    public function aggregateId(): string
    {
        return $this->id->toString();
    }

    protected function applyCourseCreated(CourseCreated $event): void
    {
        $this->id = CourseId::fromString($event->courseId);
        $this->title = $event->title;
        $this->description = $event->description;
        $this->status = CourseStatus::Draft;
    }

    protected function applyCourseRenamed(CourseRenamed $event): void
    {
        $this->title = $event->title;
        $this->description = $event->description;
    }

    protected function applyCoursePublished(CoursePublished $event): void
    {
        $this->status = CourseStatus::Published;
    }

    private static function validatedTitle(string $title): string
    {
        $title = trim($title);

        if ('' === $title) {
            throw InvalidCourseTitle::empty();
        }

        return $title;
    }
}