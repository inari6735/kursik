<?php declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Logout for cookie-based JWT auth: clears both auth cookies and revokes all of
 * the user's refresh tokens. The current access JWT stays valid until its TTL
 * (max 15 min) — accepted trade-off, documented in the auth spec.
 */
#[AsEventListener]
final readonly class CookieLogoutListener
{
    public function __construct(
        private JwtCookieFactory $cookieFactory,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();

        if ($user instanceof UserInterface) {
            $this->entityManager->createQuery(
                \sprintf('DELETE FROM %s rt WHERE rt.username = :username', RefreshToken::class),
            )->execute(['username' => $user->getUserIdentifier()]);
        }

        $response = new RedirectResponse($this->urlGenerator->generate('course_index'));

        foreach ($this->cookieFactory->expire() as $cookie) {
            $response->headers->setCookie($cookie);
        }

        $event->setResponse($response);
    }
}