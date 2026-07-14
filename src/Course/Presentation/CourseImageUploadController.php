<?php declare(strict_types=1);

namespace App\Course\Presentation;

use App\Access\Domain\Permission;
use App\Course\Infrastructure\CourseImageStorage;
use App\Shared\Infrastructure\Http\SameOriginRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Upload target for the Editor.js image tool. Responds in its expected format:
 * {success: 1, file: {url}} or {success: 0, message}.
 */
final class CourseImageUploadController extends AbstractController
{
    private const int MAX_SIZE_BYTES = 5 * 1024 * 1024;

    #[Route('/courses/images', name: 'course_image_upload', methods: ['POST'])]
    public function __invoke(Request $request, CourseImageStorage $storage): JsonResponse
    {
        if (!$this->isGranted(Permission::CourseCreate->value) && !$this->isGranted(Permission::CourseRename->value)) {
            throw $this->createAccessDeniedException();
        }

        if (!SameOriginRequest::isSatisfiedBy($request)) {
            return $this->json(['success' => 0, 'message' => 'Invalid request.'], 400);
        }

        $file = $request->files->get('image');

        if ($file instanceof UploadedFile && \in_array($file->getError(), [\UPLOAD_ERR_INI_SIZE, \UPLOAD_ERR_FORM_SIZE], true)) {
            return $this->json(['success' => 0, 'message' => 'Image is too large for the server upload limit.'], 400);
        }

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return $this->json(['success' => 0, 'message' => 'No valid file uploaded.'], 400);
        }

        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            return $this->json(['success' => 0, 'message' => 'Image is larger than 5 MB.'], 400);
        }

        if (!$storage->supports($file)) {
            return $this->json(['success' => 0, 'message' => 'Only PNG, JPEG, GIF and WebP images are allowed.'], 400);
        }

        return $this->json(['success' => 1, 'file' => ['url' => $storage->store($file)]]);
    }
}
