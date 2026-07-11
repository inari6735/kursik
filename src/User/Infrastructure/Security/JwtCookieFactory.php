<?php declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Single owner of the auth cookies: issues the access-JWT + refresh-token pair
 * and produces their expired counterparts for logout.
 */
final readonly class JwtCookieFactory
{
    public const string ACCESS_COOKIE = 'AUTH_TOKEN';
    public const string REFRESH_COOKIE = 'REFRESH_TOKEN';

    /** Refresh token TTL — must match gesdinet_jwt_refresh_token.ttl */
    private const int REFRESH_TTL = 604800;

    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
    ) {
    }

    /**
     * Creates a fresh access JWT and a fresh (persisted) refresh token for the user.
     *
     * @return array{0: Cookie, 1: Cookie} [access cookie, refresh cookie]
     */
    public function issueFor(UserInterface $user, bool $secure): array
    {
        $jwt = $this->jwtManager->create($user);

        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, self::REFRESH_TTL);
        $this->refreshTokenManager->save($refreshToken);

        return [
            $this->cookie(self::ACCESS_COOKIE, $jwt, $secure),
            $this->cookie(self::REFRESH_COOKIE, (string) $refreshToken->getRefreshToken(), $secure),
        ];
    }

    /**
     * @return list<Cookie> cookies that clear both auth cookies in the browser
     */
    public function expire(): array
    {
        return [
            Cookie::create(self::ACCESS_COOKIE)->withExpires(1)->withPath('/')->withHttpOnly(true),
            Cookie::create(self::REFRESH_COOKIE)->withExpires(1)->withPath('/')->withHttpOnly(true),
        ];
    }

    private function cookie(string $name, string $value, bool $secure): Cookie
    {
        // Both cookies live as long as the refresh token; the JWT inside the access
        // cookie expires much sooner and is silently rotated.
        return Cookie::create($name, $value, time() + self::REFRESH_TTL, '/', null, $secure, true, false, Cookie::SAMESITE_LAX);
    }
}