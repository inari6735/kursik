<?php declare(strict_types=1);

namespace App\User\Application\Command;

use App\Shared\Application\Bus\Command;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterUser implements Command
{
    public function __construct(
        #[Assert\Uuid]
        public string $userId,
        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 180)]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        public string $plainPassword,
    ) {
    }
}