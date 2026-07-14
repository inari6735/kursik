<?php declare(strict_types=1);

namespace App\Course\Infrastructure;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

/**
 * Stores course-content images under public/uploads/courses with random names.
 * The extension comes from the sniffed MIME type, never from the client filename.
 */
final readonly class CourseImageStorage
{
    private const array EXTENSION_BY_MIME = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/courses')]
        private string $storageDir,
    ) {
    }

    public function supports(UploadedFile $file): bool
    {
        return isset(self::EXTENSION_BY_MIME[$file->getMimeType()]);
    }

    /**
     * @return string the public URL path of the stored image
     */
    public function store(UploadedFile $file): string
    {
        $extension = self::EXTENSION_BY_MIME[$file->getMimeType()]
            ?? throw new \InvalidArgumentException(\sprintf('Unsupported image type "%s".', $file->getMimeType()));

        $name = Uuid::v7()->toRfc4122().'.'.$extension;
        $file->move($this->storageDir, $name);

        return '/uploads/courses/'.$name;
    }
}
