<?php declare(strict_types=1);

namespace App\Access\Application\Command;

use App\Access\Domain\Permission;
use App\Shared\Application\Bus\Command;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateRolePermissions implements Command
{
    /**
     * @param list<string> $permissions values from the Permission enum
     */
    public function __construct(
        #[Assert\Uuid]
        public string $roleId,
        #[Assert\All([new Assert\Choice(callback: [Permission::class, 'values'])])]
        public array $permissions,
    ) {
    }
}
