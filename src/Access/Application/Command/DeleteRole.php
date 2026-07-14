<?php declare(strict_types=1);

namespace App\Access\Application\Command;

use App\Shared\Application\Bus\Command;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class DeleteRole implements Command
{
    public function __construct(
        #[Assert\Uuid]
        public string $roleId,
    ) {
    }
}
