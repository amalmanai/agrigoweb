<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add owner_id foreign key columns to parcelles and cultures tables.';
    }

    public function up(Schema $schema): void
    {
        // Add owner_id column to parcelles table
        // $this->addSql('ALTER TABLE parcelles ADD COLUMN owner_id INT DEFAULT NULL');
        // $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT FK_PARCELLES_OWNER FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        // $this->addSql('CREATE INDEX IDX_PARCELLES_OWNER ON parcelles (owner_id)');

        // Add owner_id column to cultures table
        // $this->addSql('ALTER TABLE cultures ADD COLUMN owner_id INT DEFAULT NULL');
        // $this->addSql('ALTER TABLE cultures ADD CONSTRAINT FK_CULTURES_OWNER FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        // $this->addSql('CREATE INDEX IDX_CULTURES_OWNER ON cultures (owner_id)');
    }

    public function down(Schema $schema): void
    {
        // Drop constraints and columns from cultures
        // $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY FK_CULTURES_OWNER');
        // $this->addSql('DROP INDEX IDX_CULTURES_OWNER ON cultures');
        // $this->addSql('ALTER TABLE cultures DROP COLUMN owner_id');

        // Drop constraints and columns from parcelles
        // $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY FK_PARCELLES_OWNER');
        // $this->addSql('DROP INDEX IDX_PARCELLES_OWNER ON parcelles');
        // $this->addSql('ALTER TABLE parcelles DROP COLUMN owner_id');
    }
}
