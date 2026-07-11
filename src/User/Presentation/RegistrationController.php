<?php declare(strict_types=1);

namespace App\User\Presentation;

use App\Shared\Application\Bus\CommandBus;
use App\User\Application\Command\RegisterUser;
use App\User\Domain\UserRepository;
use App\User\Presentation\Form\RegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly UserRepository $users,
    ) {
    }

    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        $form = $this->createForm(RegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($this->users->emailExists($data['email'])) {
                $form->get('email')->addError(new FormError('This email is already registered.'));
            } else {
                $this->commandBus->dispatch(new RegisterUser(
                    Uuid::v7()->toRfc4122(),
                    $data['email'],
                    $data['plainPassword'],
                ));
                $this->addFlash('success', 'Account created — you can log in now.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('registration/register.html.twig', ['form' => $form]);
    }
}