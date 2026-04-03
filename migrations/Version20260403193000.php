<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create alertes_risques and historique_cultures with SQL-aligned indexes and constraints.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE alertes_risques (
            id_alerte INT AUTO_INCREMENT NOT NULL,
            id_culture INT DEFAULT NULL,
            type_alerte VARCHAR(50) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            date_alerte TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
            INDEX id_culture (id_culture),
            PRIMARY KEY(id_alerte)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE historique_cultures (
            id_historique INT AUTO_INCREMENT NOT NULL,
            id_parcelle INT DEFAULT NULL,
            ancienne_culture VARCHAR(100) DEFAULT NULL,
            date_recolte_effective DATE DEFAULT NULL,
            rendement_final DOUBLE PRECISION DEFAULT NULL,
            INDEX id_parcelle (id_parcelle),
            PRIMARY KEY(id_historique)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE historique_cultures ADD CONSTRAINT historique_cultures_ibfk_1 FOREIGN KEY (id_parcelle) REFERENCES parcelles (id_parcelle) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE historique_cultures DROP FOREIGN KEY historique_cultures_ibfk_1');
        $this->addSql('DROP TABLE historique_cultures');
        $this->addSql('DROP TABLE alertes_risques');
    }
}
