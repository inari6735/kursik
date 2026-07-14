<?php declare(strict_types=1);

namespace App\Access\Presentation;

use App\Access\Application\Command\CreateRole;
use App\Access\Application\Command\DeleteRole;
use App\Access\Application\Command\UpdateRolePermissions;
use App\Access\Application\Query\FindRole;
use App\Access\Application\Query\ListRoles;
use App\Access\Application\Query\RoleDetail;
use App\Access\Domain\Exception\ProtectedRole;
use App\Access\Domain\Exception\RoleNameTaken;
use App\Access\Domain\Exception\RoleNotFound;
use App\Access\Domain\Permission;
use App\Access\Presentation\Form\RoleType;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class AdminRoleController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('/admin/roles', name: 'admin_roles', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(Permission::AccessManage->value);

        return $this->render('admin/roles/index.html.twig', [
            'roles' => $this->queryBus->ask(new ListRoles()),
        ]);
    }

    #[Route('/admin/roles/new', name: 'admin_role_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted(Permission::AccessManage->value);

        $form = $this->createForm(RoleType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $roleId = Uuid::v7()->toRfc4122();

            try {
                $this->commandBus->dispatch(new CreateRole($roleId, $form->getData()['name']));
                $this->addFlash('success', 'Role created — now pick its permissions.');

                return $this->redirectToRoute('admin_role_edit', ['id' => $roleId]);
            } catch (RoleNameTaken $exception) {
                $form->get('name')->addError(new FormError($exception->getMessage()));
            }
        }

        return $this->render('admin/roles/new.html.twig', ['form' => $form]);
    }

    #[Route('/admin/roles/{id}', name: 'admin_role_edit', requirements: ['id' => Requirement::UUID], methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Permission::AccessManage->value);

        $role = $this->queryBus->ask(new FindRole($id));

        if (!$role instanceof RoleDetail) {
            throw $this->createNotFoundException(\sprintf('Role "%s" was not found.', $id));
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('role-permissions-'.$id, $request->getPayload()->getString('_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');

                return $this->redirectToRoute('admin_role_edit', ['id' => $id]);
            }

            try {
                $this->commandBus->dispatch(new UpdateRolePermissions($id, $request->getPayload()->all('permissions')));
                $this->addFlash('success', \sprintf('Permissions of "%s" updated. They take effect on the next token (≤15 min or re-login).', $role->name));
            } catch (ProtectedRole|RoleNotFound $exception) {
                $this->addFlash('error', $exception->getMessage());
            } catch (ValidationFailedException) {
                $this->addFlash('error', 'Unknown permission submitted.');
            }

            return $this->redirectToRoute('admin_role_edit', ['id' => $id]);
        }

        return $this->render('admin/roles/edit.html.twig', [
            'role' => $role,
            'groups' => Permission::grouped(),
        ]);
    }

    #[Route('/admin/roles/{id}/delete', name: 'admin_role_delete', requirements: ['id' => Requirement::UUID], methods: ['POST'])]
    public function delete(string $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Permission::AccessManage->value);

        if (!$this->isCsrfTokenValid('delete-role-'.$id, $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');

            return $this->redirectToRoute('admin_roles');
        }

        try {
            $this->commandBus->dispatch(new DeleteRole($id));
            $this->addFlash('success', 'Role deleted and removed from all users.');
        } catch (ProtectedRole|RoleNotFound $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('admin_roles');
    }
}
