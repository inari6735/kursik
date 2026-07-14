<?php declare(strict_types=1);

namespace App\Access\Application\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.query')]
final readonly class ListRolesHandler
{
    public function __construct(
        private AccessReadModel $readModel,
    ) {
    }

    /**
     * @return list<RoleView>
     */
    public function __invoke(ListRoles $query): array
    {
        return $this->readModel->roles();
    }
}
