<?php declare(strict_types=1);

namespace App\Access\Presentation;

use App\Access\Application\Command\AssignUserRoles;
use App\Access\Application\Query\FindUserWithRoles;
use App\Access\Application\Query\ListRoles;
use App\Access\Application\Query\ListUsersWithRoles;
use App\Access\Application\Query\UserWithRoles;
use App\Access\Domain\Exception\LastAdminWouldBeLost;
use App\Access\Domain\Exception\RoleNotFound;
use App\Access\Domain\Exception\UserNotFound;
use App\Access\Domain\Permission;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\QueryBus;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {
    }

    #[Route('/admin/users', name: 'admin_users', methods: ['GET'])]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted(Permission::AccessManage->value);

        return $this->render('admin/users/index.html.twig', [
            'users' => $this->queryBus->ask(new ListUsersWithRoles()),
        ]);
    }

    #[Route('/admin/users/{id}/roles', name: 'admin_user_roles', requirements: ['id' => Requirement::UUID], methods: ['GET', 'POST'])]
    public function editRoles(string $id, Request $request): Response
    {
        $this->denyAccessUnlessGranted(Permission::AccessManage->value);

        $user = $this->queryBus->ask(new FindUserWithRoles($id));

        if (!$user instanceof UserWithRoles) {
            throw $this->createNotFoundException(\sprintf('User "%s" was not found.', $id));
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('assign-roles-'.$id, $request->getPayload()->getString('_token'))) {
                $this->addFlash('error', 'Invalid CSRF token.');

                return $this->redirectToRoute('admin_user_roles', ['id' => $id]);
            }

            try {
                $this->commandBus->dispatch(new AssignUserRoles($id, $request->getPayload()->all('roles')));
                $this->addFlash('success', \sprintf('Roles of %s updated. They take effect on the next token (≤15 min or re-login).', $user->email));
            } catch (LastAdminWouldBeLost|RoleNotFound|UserNotFound $exception) {
                $this->addFlash('error', $exception->getMessage());
            }

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/roles.html.twig', [
            'user' => $user,
            'roles' => $this->queryBus->ask(new ListRoles()),
        ]);
    }
}
