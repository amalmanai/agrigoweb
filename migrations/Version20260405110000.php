<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add owner_id on parcelles and cultures for per-user data ownership.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE parcelles ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT FK_PARCELLES_OWNER FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_PARCELLES_OWNER ON parcelles (owner_id)');

        $this->addSql('ALTER TABLE cultures ADD owner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cultures ADD CONSTRAINT FK_CULTURES_OWNER FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_CULTURES_OWNER ON cultures (owner_id)');

        // Keep existing data usable by inheriting culture owner from related parcelle when available.
        $this->addSql('UPDATE cultures c LEFT JOIN parcelles p ON c.id_parcelle = p.id_parcelle SET c.owner_id = p.owner_id WHERE c.owner_id IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY FK_CULTURES_OWNER');
        $this->addSql('DROP INDEX IDX_CULTURES_OWNER ON cultures');
        $this->addSql('ALTER TABLE cultures DROP owner_id');

        $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY FK_PARCELLES_OWNER');
        $this->addSql('DROP INDEX IDX_PARCELLES_OWNER ON parcelles');
        $this->addSql('ALTER TABLE parcelles DROP owner_id');
    }
}
