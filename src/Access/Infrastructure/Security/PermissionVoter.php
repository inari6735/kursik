<?php declare(strict_types=1);

namespace App\Access\Infrastructure\Security;

use App\Access\Domain\Permission;
use App\Access\Domain\Role;
use App\Access\Domain\RoleRepository;
use App\User\Domain\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * The only permission guard. Attributes are Permission enum values
 * (is_granted('course.create')). For a TokenUser the decision comes from the
 * JWT claim (no DB); for a freshly authenticated User entity (the login request
 * itself) it is computed from the role repository.
 *
 * @extends Voter<string, mixed>
 */
final class PermissionVoter extends Voter
{
    public function __construct(
        private readonly RoleRepository $roles,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return null !== Permission::tryFrom($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if ($user instanceof TokenUser) {
            return \in_array($attribute, $user->permissions(), true);
        }

        if ($user instanceof User) {
            foreach ($this->roles->byNames($user->roleNames()) as $role) {
                if ($role->grants(Permission::from($attribute))) {
                    return true;
                }
            }
        }

        return false;
    }
}
