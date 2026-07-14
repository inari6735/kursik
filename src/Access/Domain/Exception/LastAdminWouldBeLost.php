<?php declare(strict_types=1);

namespace App\Access\Domain\Exception;

use App\Access\Domain\Role;

final class LastAdminWouldBeLost extends \DomainException
{
    public static function create(): self
    {
        return new self(\sprintf('At least one user must keep the "%s" role.', Role::ADMIN));
    }
}
