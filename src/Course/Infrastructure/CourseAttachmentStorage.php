<?php declare(strict_types=1);

namespace App\Course\Infrastructure;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Uid\Uuid;

/**
 * Stores course attachments (documents/archives) under public/uploads/courses/files.
 * Whitelist by sniffed MIME type; stored name is random, the original name is
 * only echoed back for display.
 */
final readonly class CourseAttachmentStorage
{
    private const array EXTENSION_BY_MIME = [
        'application/pdf' => 'pdf',
        'application/zip' => 'zip',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
    ];

    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/courses/files')]
        private string $storageDir,
    ) {
    }

    public function supports(UploadedFile $file): bool
    {
        return isset(self::EXTENSION_BY_MIME[$file->getMimeType()]);
    }

    /**
     * @return array{url: string, name: string, size: int, extension: string}
     */
    public function store(UploadedFile $file): array
    {
        $extension = self::EXTENSION_BY_MIME[$file->getMimeType()]
            ?? throw new \InvalidArgumentException(\sprintf('Unsupported attachment type "%s".', $file->getMimeType()));

        $size = (int) $file->getSize();
        $originalName = $file->getClientOriginalName();
        $storedName = Uuid::v7()->toRfc4122().'.'.$extension;

        $file->move($this->storageDir, $storedName);

        return [
            'url' => '/uploads/courses/files/'.$storedName,
            'name' => '' !== $originalName ? $originalName : $storedName,
            'size' => $size,
            'extension' => $extension,
        ];
    }
}
