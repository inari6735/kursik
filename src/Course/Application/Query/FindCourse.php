<?php declare(strict_types=1);

namespace App\Course\Application\Query;

use App\Shared\Application\Bus\Query;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class FindCourse implements Query
{
    public function __construct(
        #[Assert\Uuid]
        public string $courseId,
    ) {
    }
}