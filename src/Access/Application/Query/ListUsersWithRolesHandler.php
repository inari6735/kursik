<?php declare(strict_types=1);

namespace App\Access\Application\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.query')]
final readonly class ListUsersWithRolesHandler
{
    public function __construct(
        private AccessReadModel $readModel,
    ) {
    }

    /**
     * @return list<UserWithRoles>
     */
    public function __invoke(ListUsersWithRoles $query): array
    {
        return $this->readModel->users();
    }
}
