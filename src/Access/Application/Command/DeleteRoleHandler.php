<?php declare(strict_types=1);

namespace App\Access\Application\Command;

use App\Access\Application\UserRoleAssignments;
use App\Access\Domain\Exception\ProtectedRole;
use App\Access\Domain\Exception\RoleNotFound;
use App\Access\Domain\Role;
use App\Access\Domain\RoleRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class DeleteRoleHandler
{
    public function __construct(
        private RoleRepository $roles,
        private UserRoleAssignments $assignments,
    ) {
    }

    public function __invoke(DeleteRole $command): void
    {
        $role = $this->roles->byId(Uuid::fromString($command->roleId))
            ?? throw RoleNotFound::withId($command->roleId);

        if (Role::ADMIN === $role->name()) {
            throw ProtectedRole::cannotBeDeleted();
        }

        $this->assignments->removeRoleFromAllUsers($role->name());
        $this->roles->remove($role);
    }
}
