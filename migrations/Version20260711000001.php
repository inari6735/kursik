<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the course_detail and course_list read-model tables';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration targets PostgreSQL.');

        $this->addSql(<<<'SQL'
            CREATE TABLE course_detail (
                id           UUID PRIMARY KEY,
                title        VARCHAR(255) NOT NULL,
                description  TEXT NOT NULL DEFAULT '',
                status       VARCHAR(20) NOT NULL,
                created_at   TIMESTAMPTZ NOT NULL,
                published_at TIMESTAMPTZ
            )
            SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE course_list (
                id         UUID PRIMARY KEY,
                title      VARCHAR(255) NOT NULL,
                status     VARCHAR(20) NOT NULL,
                created_at TIMESTAMPTZ NOT NULL
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE course_list');
        $this->addSql('DROP TABLE course_detail');
    }
}