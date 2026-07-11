<?php declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\UserRepository;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Silent refresh: when the access JWT is missing or expired but a valid refresh
 * token cookie is present, rotate the refresh token (single use), mint a fresh
 * JWT, swap it into the request so the firewall authenticates normally, and
 * attach both new cookies to the response. The user never sees a redirect.
 *
 * Runs at priority 9 — just before the security firewall (priority 8).
 */
final class SilentRefreshListener implements EventSubscriberInterface
{
    private const string COOKIES_ATTRIBUTE = '_rotated_auth_cookies';

    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly UserRepository $users,
        private readonly JwtCookieFactory $cookieFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 9],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $accessToken = $request->cookies->get(JwtCookieFactory::ACCESS_COOKIE);
        if (null !== $accessToken && $this->isTokenValid($accessToken)) {
            return;
        }

        $refreshTokenValue = $request->cookies->get(JwtCookieFactory::REFRESH_COOKIE);
        if (null === $refreshTokenValue || '' === $refreshTokenValue) {
            return;
        }

        $storedToken = $this->refreshTokenManager->get($refreshTokenValue);
        if (null === $storedToken || !$storedToken->isValid()) {
            // Unknown (possibly already rotated) or expired token: no silent auth,
            // the entry point will send the user to /login.
            return;
        }

        $user = $this->users->byEmail((string) $storedToken->getUsername());
        if (null === $user) {
            return;
        }

        // Rotation: the presented token is consumed and can never be used again.
        $this->refreshTokenManager->delete($storedToken);

        [$accessCookie, $refreshCookie] = $this->cookieFactory->issueFor($user, $request->isSecure());

        // Let the firewall see the fresh JWT on this very request.
        $request->cookies->set(JwtCookieFactory::ACCESS_COOKIE, (string) $accessCookie->getValue());
        $request->attributes->set(self::COOKIES_ATTRIBUTE, [$accessCookie, $refreshCookie]);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        /** @var list<Cookie> $cookies */
        $cookies = $event->getRequest()->attributes->get(self::COOKIES_ATTRIBUTE, []);

        foreach ($cookies as $cookie) {
            $event->getResponse()->headers->setCookie($cookie);
        }
    }

    private function isTokenValid(string $token): bool
    {
        try {
            return [] !== $this->jwtManager->parse($token);
        } catch (JWTDecodeFailureException) {
            return false;
        }
    }
}