<?php declare(strict_types=1);

namespace App\Access\Application\Command;

use App\Access\Domain\Exception\ProtectedRole;
use App\Access\Domain\Exception\RoleNotFound;
use App\Access\Domain\Permission;
use App\Access\Domain\Role;
use App\Access\Domain\RoleRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class UpdateRolePermissionsHandler
{
    public function __construct(
        private RoleRepository $roles,
    ) {
    }

    public function __invoke(UpdateRolePermissions $command): void
    {
        $role = $this->roles->byId(Uuid::fromString($command->roleId))
            ?? throw RoleNotFound::withId($command->roleId);

        $permissions = array_map(Permission::from(...), $command->permissions);

        if (Role::ADMIN === $role->name() && !\in_array(Permission::AccessManage, $permissions, true)) {
            throw ProtectedRole::mustKeepAccessManage();
        }

        $role->changePermissions($permissions);
        $this->roles->save($role);
    }
}
