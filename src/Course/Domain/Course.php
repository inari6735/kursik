<?php declare(strict_types=1);

namespace App\Course\Domain;

use App\Course\Domain\Exception\CourseAlreadyPublished;
use App\Course\Domain\Exception\InvalidCourseTitle;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/**
 * Classic ORM entity — state lives in the courses table. Invariants stay in
 * the domain methods; the ORM only persists the outcome.
 */
#[ORM\Entity]
#[ORM\Table(name: 'courses')]
class Course
{
    private function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME)]
        private Uuid $id,
        #[ORM\Column(length: 255)]
        private string $title,
        #[ORM\Column(type: Types::TEXT)]
        private string $description,
        #[ORM\Column(length: 20, enumType: CourseStatus::class)]
        private CourseStatus $status,
        #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
        private \DateTimeImmutable $createdAt,
        #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true)]
        private ?\DateTimeImmutable $publishedAt = null,
        /** @var array{blocks: list<array<string, mixed>>}|null Editor.js block data */
        #[ORM\Column(type: Types::JSON, nullable: true)]
        private ?array $content = null,
    ) {
    }

    public static function create(CourseId $id, string $title, string $description, \DateTimeImmutable $now, ?array $content = null): self
    {
        return new self(
            Uuid::fromString($id->toString()),
            self::validatedTitle($title),
            $description,
            CourseStatus::Draft,
            $now,
            content: $content,
        );
    }

    public function rename(string $title, string $description, ?array $content = null): void
    {
        if (CourseStatus::Published === $this->status) {
            throw CourseAlreadyPublished::withId($this->id());
        }

        $this->title = self::validatedTitle($title);
        $this->description = $description;
        $this->content = $content;
    }

    public function publish(\DateTimeImmutable $now): void
    {
        if (CourseStatus::Published === $this->status) {
            throw CourseAlreadyPublished::withId($this->id());
        }

        $this->status = CourseStatus::Published;
        $this->publishedAt = $now;
    }

    public function id(): CourseId
    {
        return CourseId::fromString($this->id->toRfc4122());
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function status(): CourseStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function publishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function content(): ?array
    {
        return $this->content;
    }

    private static function validatedTitle(string $title): string
    {
        $title = trim($title);

        if ('' === $title) {
            throw InvalidCourseTitle::empty();
        }

        return $title;
    }
}
