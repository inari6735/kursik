<?php declare(strict_types=1);

namespace App\Access\Application\Command;

use App\Shared\Application\Bus\Command;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateRole implements Command
{
    public function __construct(
        #[Assert\Uuid]
        public string $roleId,
        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^[a-z][a-z0-9_]{1,29}$/', message: 'Use 2-30 chars: lowercase letters, digits, underscores; start with a letter.')]
        public string $name,
    ) {
    }
}
