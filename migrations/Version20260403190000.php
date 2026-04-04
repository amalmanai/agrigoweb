<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create parcelles and cultures tables with SQL schema-aligned constraints.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE parcelles (
            id_parcelle INT AUTO_INCREMENT NOT NULL,
            nom_parcelle VARCHAR(100) NOT NULL,
            surface DOUBLE PRECISION NOT NULL,
            coordonnees_gps VARCHAR(255) DEFAULT NULL,
            type_sol VARCHAR(50) DEFAULT NULL,
            PRIMARY KEY(id_parcelle)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE cultures (
            id_culture INT AUTO_INCREMENT NOT NULL,
            id_parcelle INT DEFAULT NULL,
            nom_culture VARCHAR(100) NOT NULL,
            date_semis DATE NOT NULL,
            etat_croissance VARCHAR(50) DEFAULT NULL,
            rendement_prevu DOUBLE PRECISION DEFAULT NULL,
            INDEX IDX_CULTURES_ID_PARCELLE (id_parcelle),
            PRIMARY KEY(id_culture)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE cultures ADD CONSTRAINT cultures_ibfk_1 FOREIGN KEY (id_parcelle) REFERENCES parcelles (id_parcelle) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY cultures_ibfk_1');
        $this->addSql('DROP TABLE cultures');
        $this->addSql('DROP TABLE parcelles');
    }
}
