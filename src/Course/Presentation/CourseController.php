<?php declare(strict_types=1);

namespace App\Course\Presentation;

use App\Course\Application\Command\CreateCourse;
use App\Course\Application\Command\PublishCourse;
use App\Course\Application\Command\RenameCourse;
use App\Course\Application\Query\CourseDetail;
use App\Course\Application\Query\FindCourse;
use App\Course\Application\Query\ListCourses;
use App\Access\Domain\Permission;
use App\Course\Domain\CourseId;
use App\Course\Domain\Exception\CourseAlreadyPublished;
use App\Course\Presentation\Form\CourseType;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class CourseController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('/courses', name: 'course_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('course/index.html.twig', [
            'courses' => $this->queryBus->ask(new ListCourses()),
        ]);
    }

    #[Route('/courses/new', name: 'course_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(Permission::CourseCreate->value);

        $form = $this->createForm(CourseType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $id = CourseId::generate();

            $this->commandBus->dispatch(new CreateCourse($id->toString(), $data['title'], $data['description'], $data['content'] ?: null));
            $this->addFlash('success', 'Course created.');

            return $this->redirectToRoute('course_show', ['id' => $id->toString()]);
        }

        return $this->render('course/new.html.twig', ['form' => $form]);
    }

    #[Route('/courses/{id}', name: 'course_show', requirements: ['id' => Requirement::UUID], methods: ['GET'])]
    public function show(string $id): Response
    {
        return $this->render('course/show.html.twig', ['course' => $this->findCourseOr404($id)]);
    }

    #[Route('/courses/{id}/rename', name: 'course_rename', requirements: ['id' => Requirement::UUID], methods: ['GET', 'POST'])]
    public function rename(string $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Permission::CourseRename->value);

        $course = $this->findCourseOr404($id);

        $form = $this->createForm(CourseType::class, [
            'title' => $course->title,
            'description' => $course->description,
            'content' => null !== $course->content ? json_encode($course->content, \JSON_THROW_ON_ERROR) : '',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            try {
                $this->commandBus->dispatch(new RenameCourse($id, $data['title'], $data['description'], $data['content'] ?: null));
                $this->addFlash('success', 'Course renamed.');
            } catch (CourseAlreadyPublished) {
                $this->addFlash('error', 'A published course cannot be renamed.');
            }

            return $this->redirectToRoute('course_show', ['id' => $id]);
        }

        return $this->render('course/rename.html.twig', ['form' => $form, 'course' => $course]);
    }

    #[Route('/courses/{id}/publish', name: 'course_publish', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function publish(string $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Permission::CoursePublish->value);

        if (!$this->isCsrfTokenValid('publish-course-'.$id, $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('course_show', ['id' => $id]);
        }

        try {
            $this->commandBus->dispatch(new PublishCourse($id));
            $this->addFlash('success', 'Course published.');
        } catch (CourseAlreadyPublished) {
            $this->addFlash('warning', 'This course is already published.');
        }

        return $this->redirectToRoute('course_show', ['id' => $id]);
    }

    private function findCourseOr404(string $id): CourseDetail
    {
        $course = $this->queryBus->ask(new FindCourse($id));

        if (!$course instanceof CourseDetail) {
            throw $this->createNotFoundException(\sprintf('Course "%s" was not found.', $id));
        }

        return $course;
    }
}