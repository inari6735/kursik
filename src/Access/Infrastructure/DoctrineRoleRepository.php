<?php declare(strict_types=1);

namespace App\Access\Infrastructure;

use App\Access\Domain\Role;
use App\Access\Domain\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class DoctrineRoleRepository implements RoleRepository
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function all(): array
    {
        return $this->entityManager->getRepository(Role::class)->findBy([], ['name' => 'ASC']);
    }

    public function byId(Uuid $id): ?Role
    {
        return $this->entityManager->find(Role::class, $id);
    }

    public function byName(string $name): ?Role
    {
        return $this->entityManager->getRepository(Role::class)->findOneBy(['name' => $name]);
    }

    public function byNames(array $names): array
    {
        if ([] === $names) {
            return [];
        }

        return $this->entityManager->getRepository(Role::class)->findBy(['name' => $names]);
    }

    public function nameExists(string $name): bool
    {
        return null !== $this->byName($name);
    }

    public function add(Role $role): void
    {
        $this->entityManager->persist($role);
        $this->entityManager->flush();
    }

    public function save(Role $role): void
    {
        $this->entityManager->flush();
    }

    public function remove(Role $role): void
    {
        $this->entityManager->remove($role);
        $this->entityManager->flush();
    }
}
