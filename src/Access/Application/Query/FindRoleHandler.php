<?php declare(strict_types=1);

namespace App\Access\Application\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'messenger.bus.query')]
final readonly class FindRoleHandler
{
    public function __construct(
        private AccessReadModel $readModel,
    ) {
    }

    public function __invoke(FindRole $query): ?RoleDetail
    {
        return $this->readModel->roleById($query->roleId);
    }
}
