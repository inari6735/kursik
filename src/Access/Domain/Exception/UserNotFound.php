<?php declare(strict_types=1);

namespace App\Access\Domain\Exception;

final class UserNotFound extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self(\sprintf('User "%s" was not found.', $id));
    }

    public static function withEmail(string $email): self
    {
        return new self(\sprintf('User "%s" was not found.', $email));
    }
}
