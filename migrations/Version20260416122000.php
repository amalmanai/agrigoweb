<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416122000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing vente columns to match the current entity mapping.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vente ADD recolte_id INT DEFAULT NULL, ADD buyer_name VARCHAR(255) DEFAULT NULL, ADD status VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_VENTE_RECOLTE ON vente (recolte_id)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_VENTE_RECOLTE FOREIGN KEY (recolte_id) REFERENCES recolte (id_recolte) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_VENTE_RECOLTE');
        $this->addSql('DROP INDEX IDX_VENTE_RECOLTE ON vente');
        $this->addSql('ALTER TABLE vente DROP COLUMN recolte_id, DROP COLUMN buyer_name, DROP COLUMN status');
    }
}