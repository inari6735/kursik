<?php declare(strict_types=1);

namespace App\Access\Domain\Exception;

use App\Access\Domain\Role;

final class ProtectedRole extends \DomainException
{
    public static function cannotBeDeleted(): self
    {
        return new self(\sprintf('The "%s" role is protected and cannot be deleted.', Role::ADMIN));
    }

    public static function mustKeepAccessManage(): self
    {
        return new self(\sprintf('The "%s" role must keep the "access.manage" permission.', Role::ADMIN));
    }
}
