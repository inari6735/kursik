<?php declare(strict_types=1);

namespace App\Course\Domain\Exception;

use App\Course\Domain\CourseId;

final class CourseNotFound extends \DomainException
{
    public static function withId(CourseId $id): self
    {
        return new self(\sprintf('Course "%s" was not found.', $id));
    }
}