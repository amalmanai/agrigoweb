<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Link recolte to parcelles with nullable parcelle_id foreign key';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recolte ADD parcelle_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_2E02095895B5C063 ON recolte (parcelle_id)');
        $this->addSql('ALTER TABLE recolte ADD CONSTRAINT FK_2E02095895B5C063 FOREIGN KEY (parcelle_id) REFERENCES parcelles (id_parcelle) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE recolte DROP FOREIGN KEY FK_2E02095895B5C063');
        $this->addSql('DROP INDEX IDX_2E02095895B5C063 ON recolte');
        $this->addSql('ALTER TABLE recolte DROP parcelle_id');
    }
}
