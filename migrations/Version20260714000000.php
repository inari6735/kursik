<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the roles table and seed the protected admin role';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration targets PostgreSQL.');

        $this->addSql(<<<'SQL'
            CREATE TABLE roles (
                id          UUID PRIMARY KEY,
                name        VARCHAR(30) NOT NULL,
                permissions JSON NOT NULL,
                CONSTRAINT uniq_roles_name UNIQUE (name)
            )
            SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO roles (id, name, permissions) VALUES (
                gen_random_uuid(),
                'admin',
                '["course.create","course.rename","course.publish","access.manage"]'
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE roles');
    }
}
