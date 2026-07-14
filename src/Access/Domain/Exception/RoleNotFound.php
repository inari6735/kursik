<?php declare(strict_types=1);

namespace App\Access\Domain\Exception;

final class RoleNotFound extends \DomainException
{
    public static function withName(string $name): self
    {
        return new self(\sprintf('Role "%s" was not found.', $name));
    }

    public static function withId(string $id): self
    {
        return new self(\sprintf('Role "%s" was not found.', $id));
    }
}
