<?php declare(strict_types=1);

namespace App\Access\Domain\Exception;

final class RoleNameTaken extends \DomainException
{
    public static function withName(string $name): self
    {
        return new self(\sprintf('Role "%s" already exists.', $name));
    }
}
