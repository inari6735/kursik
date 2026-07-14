<?php declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Handles POST /login: verifies credentials, then hands the user a JWT access
 * cookie + refresh token cookie instead of starting a session.
 */
final class FormLoginAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly JwtCookieFactory $cookieFactory,
        private readonly UserRepository $users,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->getPayload()->getString('_username');
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        return new Passport(
            // Explicit loader: the firewall provider is now the JWT payload provider,
            // but password login must verify against the entity.
            new UserBadge($email, $this->users->byEmail(...)),
            new PasswordCredentials($request->getPayload()->getString('_password')),
            [new CsrfTokenBadge('authenticate', $request->getPayload()->getString('_csrf_token'))],
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $targetPath = $request->getPayload()->getString('_target_path');
        $response = new RedirectResponse(
            '' !== $targetPath && str_starts_with($targetPath, '/')
                ? $targetPath
                : $this->urlGenerator->generate('course_index'),
        );

        foreach ($this->cookieFactory->issueFor($token->getUser(), $request->isSecure()) as $cookie) {
            $response->headers->setCookie($cookie);
        }

        return $response;
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate('app_login');
    }
}