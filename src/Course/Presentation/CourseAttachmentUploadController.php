<?php declare(strict_types=1);

namespace App\Course\Presentation;

use App\Access\Domain\Permission;
use App\Course\Infrastructure\CourseAttachmentStorage;
use App\Shared\Infrastructure\Http\SameOriginRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Upload target for the Editor.js attaches tool. Expected response format:
 * {success: 1, file: {url, name, size, extension}} or {success: 0, message}.
 */
final class CourseAttachmentUploadController extends AbstractController
{
    private const int MAX_SIZE_BYTES = 8 * 1024 * 1024;

    #[Route('/courses/files', name: 'course_file_upload', methods: ['POST'])]
    public function __invoke(Request $request, CourseAttachmentStorage $storage): JsonResponse
    {
        if (!$this->isGranted(Permission::CourseCreate->value) && !$this->isGranted(Permission::CourseRename->value)) {
            throw $this->createAccessDeniedException();
        }

        if (!SameOriginRequest::isSatisfiedBy($request)) {
            return $this->json(['success' => 0, 'message' => 'Invalid request.'], 400);
        }

        $file = $request->files->get('file');

        if ($file instanceof UploadedFile && \in_array($file->getError(), [\UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE], true)) {
            return $this->json(['success' => 0, 'message' => 'File is too large for the server upload limit.'], 400);
        }

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['success' => 0, 'message' => 'No valid file uploaded.'], 400);
        }

        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            return $this->json(['success' => 0, 'message' => 'File is larger than 8 MB.'], 400);
        }

        if (!$storage->supports($file)) {
            return $this->json(['success' => 0, 'message' => 'Allowed types: PDF, ZIP, TXT, CSV, DOC(X), XLS(X), PPT(X).'], 400);
        }

        return $this->json(['success' => 1, 'file' => $storage->store($file)]);
    }
}
