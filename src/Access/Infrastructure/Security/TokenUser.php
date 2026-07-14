<?php declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;

/**
 * Request-time user built purely from JWT claims — no database on the happy path.
 * Created by Lexik's payload user provider (`lexik_jwt` in security.yaml).
 */
final readonly class TokenUser implements JWTUserInterface
{
    /**
     * @param list<string> $roles
     * @param list<string> $permissions
     */
    public function __construct(
        private string $email,
        private array $roles,
        private array $permissions,
    ) {
    }

    public static function createFromPayload($username, array $payload): self
    {
        return new self(
            (string) $username,
            array_values($payload['roles'] ?? []),
            array_values($payload['permissions'] ?? []),
        );
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return $this->permissions;
    }
}
