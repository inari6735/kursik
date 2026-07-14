<?php declare(strict_types=1);

namespace App\User\Domain;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Classic ORM entity — the User context is deliberately NOT event-sourced
 * (see docs/superpowers/specs/2026-07-11-auth-jwt-design.md).
 */
#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'uniq_users_email', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Column]
    private string $password = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME)]
        private Uuid $id,
        #[ORM\Column(length: 180)]
        private string $email,
    ) {
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $hashedPassword): void
    {
        $this->password = $hashedPassword;
    }

    public function getRoles(): array
    {
        $symfonyRoles = array_map(
            static fn (string $name): string => 'ROLE_'.strtoupper($name),
            $this->roles,
        );

        return array_values(array_unique([...$symfonyRoles, 'ROLE_USER']));
    }

    /**
     * Raw role-entity names as stored (e.g. ["admin"]) — the Access context
     * resolves them to permissions.
     *
     * @return list<string>
     */
    public function roleNames(): array
    {
        return $this->roles;
    }

    /**
     * @param list<string> $names
     */
    public function assignRoleNames(array $names): void
    {
        $this->roles = array_values(array_unique($names));
    }
}