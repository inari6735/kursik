<?php declare(strict_types=1);

namespace App\Access\Domain;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'roles')]
#[ORM\UniqueConstraint(name: 'uniq_roles_name', columns: ['name'])]
class Role
{
    /** The protected bootstrap role: cannot be deleted, always keeps access.manage. */
    public const string ADMIN = 'admin';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $permissions = [];

    public function __construct(
        #[ORM\Id]
        #[ORM\Column(type: UuidType::NAME)]
        private Uuid $id,
        #[ORM\Column(length: 30)]
        private string $name,
    ) {
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return $this->permissions;
    }

    /**
     * @param list<Permission> $permissions
     */
    public function changePermissions(array $permissions): void
    {
        $values = array_map(static fn (Permission $permission): string => $permission->value, $permissions);
        $this->permissions = array_values(array_unique($values));
    }

    public function grants(Permission $permission): bool
    {
        return \in_array($permission->value, $this->permissions, true);
    }
}
