<?php declare(strict_types=1);

namespace App\Course\Domain\Exception;

final class InvalidCourseTitle extends \DomainException
{
    public static function empty(): self
    {
        return new self('Course title cannot be empty.');
    }
}