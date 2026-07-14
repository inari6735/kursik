<?php declare(strict_types=1);

namespace App\Access\Application\Command;

use App\Shared\Application\Bus\Command;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AssignUserRoles implements Command
{
    /**
     * @param list<string> $roleNames
     */
    public function __construct(
        #[Assert\Uuid]
        public string $userId,
        #[Assert\All([new Assert\Type('string'), new Assert\NotBlank()])]
        public array $roleNames,
    ) {
    }
}
