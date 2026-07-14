<?php declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use App\Access\Domain\Role;
use App\Access\Domain\RoleRepository;
use App\User\Domain\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Adds the `permissions` claim (union of the user's roles' permissions) whenever
 * a JWT is created — both at login and at silent refresh rotation. This is the
 * only moment authorization data is read from the database.
 */
#[AsEventListener(event: Events::JWT_CREATED)]
final readonly class JwtPermissionsClaimListener
{
    public function __construct(
        private RoleRepository $roles,
    ) {
    }

    public function __invoke(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        $roleNames = match (true) {
            $user instanceof User => $user->roleNames(),
            $user instanceof TokenUser => [], // never happens: tokens are minted from the entity
            default => [],
        };

        $permissions = [];
        foreach ($this->roles->byNames($roleNames) as $role) {
            $permissions = [...$permissions, ...$role->permissions()];
        }

        $payload = $event->getData();
        $payload['permissions'] = array_values(array_unique($permissions));
        $event->setData($payload);
    }
}
