<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create missing tables: systeme_irrigation, historique_irrigation, tache, produit, mouvement_stock.';
    }

    public function up(Schema $schema): void
    {
        // Tables already exist
        /*
        // Create systeme_irrigation table
        $this->addSql('CREATE TABLE systeme_irrigation (
            id_systeme INT AUTO_INCREMENT NOT NULL,
            id_parcelle INT NOT NULL,
            nom_systeme VARCHAR(255) NOT NULL,
            seuil_humidite NUMERIC(10, 2) DEFAULT NULL,
            mode VARCHAR(255) DEFAULT NULL,
            statut VARCHAR(255) DEFAULT NULL,
            date_creation DATETIME NOT NULL,
            PRIMARY KEY (id_systeme)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create historique_irrigation table
        $this->addSql('CREATE TABLE historique_irrigation (
            id INT AUTO_INCREMENT NOT NULL,
            id_systeme INT NOT NULL,
            date_irrigation DATETIME NOT NULL,
            duree_minutes INT NOT NULL,
            volume_eau NUMERIC(10, 2) DEFAULT NULL,
            humidite_avant NUMERIC(10, 2) DEFAULT NULL,
            type_declenchement VARCHAR(255) DEFAULT NULL,
            INDEX IDX_C2B514E14BE4C493 (id_systeme),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create tache table
        $this->addSql('CREATE TABLE tache (
            id INT AUTO_INCREMENT NOT NULL,
            tittre_tache VARCHAR(255) NOT NULL,
            description_tache VARCHAR(255) NOT NULL,
            type_tache VARCHAR(255) NOT NULL,
            id_user INT NOT NULL,
            date_tache DATE NOT NULL,
            heure_debut_tache TIME NOT NULL,
            heure_fin_tache TIME NOT NULL,
            status_tache VARCHAR(255) NOT NULL,
            remarque_tache VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create produit table
        $this->addSql('CREATE TABLE produit (
            id_produit INT AUTO_INCREMENT NOT NULL,
            nom_produit VARCHAR(255) NOT NULL,
            categorie VARCHAR(255) NOT NULL,
            quantite_disponible INT NOT NULL,
            unite VARCHAR(255) NOT NULL,
            seuil_alerte INT NOT NULL,
            date_expiration VARCHAR(255) DEFAULT NULL,
            prix_unitaire INT NOT NULL,
            PRIMARY KEY (id_produit)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create mouvement_stock table
        $this->addSql('CREATE TABLE mouvement_stock (
            id_mouvement INT AUTO_INCREMENT NOT NULL,
            type_mouvement VARCHAR(255) NOT NULL,
            date_mouvement VARCHAR(255) NOT NULL,
            quantite INT NOT NULL,
            motif VARCHAR(255) NOT NULL,
            id_produit INT NOT NULL,
            id_user INT NOT NULL,
            PRIMARY KEY (id_mouvement)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Add foreign key for historique_irrigation
        $this->addSql('ALTER TABLE historique_irrigation ADD CONSTRAINT FK_C2B514E14BE4C493 FOREIGN KEY (id_systeme) REFERENCES systeme_irrigation (id_systeme)');
        */
    }

    public function down(Schema $schema): void
    {
        /*
        $this->addSql('ALTER TABLE historique_irrigation DROP FOREIGN KEY FK_C2B514E14BE4C493');
        $this->addSql('DROP TABLE IF EXISTS mouvement_stock');
        $this->addSql('DROP TABLE IF EXISTS produit');
        $this->addSql('DROP TABLE IF EXISTS tache');
        $this->addSql('DROP TABLE IF EXISTS historique_irrigation');
        $this->addSql('DROP TABLE IF EXISTS systeme_irrigation');
        */
    }
}
