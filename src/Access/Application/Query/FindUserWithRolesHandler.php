<?php declare(strict_types=1);

namespace App\Access\Application\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.query')]
final readonly class FindUserWithRolesHandler
{
    public function __construct(
        private AccessReadModel $readModel,
    ) {
    }

    public function __invoke(FindUserWithRoles $query): ?UserWithRoles
    {
        return $this->readModel->userById($query->userId);
    }
}
