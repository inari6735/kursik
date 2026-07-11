<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the append-only event_store table with an optimistic-concurrency guard';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration targets PostgreSQL.');

        $this->addSql(<<<'SQL'
            CREATE TABLE event_store (
                sequence       BIGSERIAL PRIMARY KEY,
                aggregate_id   UUID NOT NULL,
                aggregate_type VARCHAR(100) NOT NULL,
                version        INT NOT NULL,
                event_type     VARCHAR(150) NOT NULL,
                payload        JSONB NOT NULL,
                occurred_at    TIMESTAMPTZ NOT NULL,
                CONSTRAINT event_store_aggregate_version UNIQUE (aggregate_id, version)
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE event_store');
    }
}