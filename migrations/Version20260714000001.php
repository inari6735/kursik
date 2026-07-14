<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Course module moves from event sourcing to a classic courses table; data is carried over from the course_detail projection';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration targets PostgreSQL.');

        $this->addSql(<<<'SQL'
            CREATE TABLE courses (
                id           UUID PRIMARY KEY,
                title        VARCHAR(255) NOT NULL,
                description  TEXT NOT NULL,
                status       VARCHAR(20) NOT NULL,
                created_at   TIMESTAMPTZ NOT NULL,
                published_at TIMESTAMPTZ
            )
            SQL);

        // course_detail was the full-fidelity projection — it becomes the source of truth.
        $this->addSql(<<<'SQL'
            INSERT INTO courses (id, title, description, status, created_at, published_at)
            SELECT id, title, description, status, created_at, published_at FROM course_detail
            SQL);

        $this->addSql('DROP TABLE course_detail');
        $this->addSql('DROP TABLE course_list');
        // event_store stays untouched: shared infrastructure + preserved history.
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The course_detail/course_list projections are gone; rebuild them from event_store if ever needed.');
    }
}
