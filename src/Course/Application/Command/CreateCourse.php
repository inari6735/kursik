<?php declare(strict_types=1);

namespace App\Course\Application\Command;

use App\Shared\Application\Bus\Command;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateCourse implements Command
{
    public function __construct(
        #[Assert\Uuid]
        public string $courseId,
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public string $title,
        public string $description,
        #[Assert\Json]
        public ?string $content = null,
    ) {
    }
}