<?php declare(strict_types=1);

namespace App\Access\Application\Command;

use App\Access\Application\UserRoleAssignments;
use App\Access\Domain\Exception\LastAdminWouldBeLost;
use App\Access\Domain\Exception\RoleNotFound;
use App\Access\Domain\Exception\UserNotFound;
use App\Access\Domain\Role;
use App\Access\Domain\RoleRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class AssignUserRolesHandler
{
    public function __construct(
        private RoleRepository $roles,
        private UserRoleAssignments $assignments,
    ) {
    }

    public function __invoke(AssignUserRoles $command): void
    {
        $requestedNames = array_values(array_unique($command->roleNames));

        $found = array_map(
            static fn (Role $role): string => $role->name(),
            $this->roles->byNames($requestedNames),
        );
        foreach ($requestedNames as $name) {
            if (!\in_array($name, $found, true)) {
                throw RoleNotFound::withName($name);
            }
        }

        $currentNames = $this->assignments->rolesOf($command->userId)
            ?? throw UserNotFound::withId($command->userId);

        $losesAdmin = \in_array(Role::ADMIN, $currentNames, true) && !\in_array(Role::ADMIN, $requestedNames, true);

        if ($losesAdmin && 0 === $this->assignments->countUsersWithRole(Role::ADMIN, $command->userId)) {
            throw LastAdminWouldBeLost::create();
        }

        $this->assignments->assign($command->userId, $requestedNames);
    }
}
