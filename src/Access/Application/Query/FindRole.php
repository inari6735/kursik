<?php declare(strict_types=1);

namespace App\Access\Application\Query;

use App\Shared\Application\Bus\Query;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FindRole implements Query
{
    public function __construct(
        #[Assert\Uuid]
        public string $roleId,
    ) {
    }
}
