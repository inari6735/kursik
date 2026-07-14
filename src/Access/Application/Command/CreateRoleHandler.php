<?php declare(strict_types=1);

namespace App\Access\Application\Command;

use App\Access\Domain\Exception\RoleNameTaken;
use App\Access\Domain\Role;
use App\Access\Domain\RoleRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class CreateRoleHandler
{
    public function __construct(
        private RoleRepository $roles,
    ) {
    }

    public function __invoke(CreateRole $command): void
    {
        if ($this->roles->nameExists($command->name)) {
            throw RoleNameTaken::withName($command->name);
        }

        $this->roles->add(new Role(Uuid::fromString($command->roleId), $command->name));
    }
}
