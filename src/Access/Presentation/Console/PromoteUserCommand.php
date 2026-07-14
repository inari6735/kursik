<?php declare(strict_types=1);

namespace App\Access\Presentation\Console;

use App\Access\Application\UserRoleAssignments;
use App\Access\Domain\Role;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Bootstrap of the first administrator — grants the protected "admin" role
 * without going through the panel (which requires an admin to exist).
 */
#[AsCommand(name: 'app:user:promote', description: 'Grants the admin role to a user by email')]
final class PromoteUserCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly UserRoleAssignments $assignments,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email of the user to promote');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');

        $userId = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', ['email' => $email]);

        if (false === $userId) {
            $io->error(\sprintf('User "%s" was not found.', $email));

            return self::FAILURE;
        }

        $roles = $this->assignments->rolesOf((string) $userId) ?? [];

        if (\in_array(Role::ADMIN, $roles, true)) {
            $io->note(\sprintf('User "%s" already has the "%s" role.', $email, Role::ADMIN));

            return self::SUCCESS;
        }

        $this->assignments->assign((string) $userId, [...$roles, Role::ADMIN]);

        $io->success(\sprintf('User "%s" now has the "%s" role. It takes effect on their next token (re-login or silent refresh, ≤15 min).', $email, Role::ADMIN));

        return self::SUCCESS;
    }
}
