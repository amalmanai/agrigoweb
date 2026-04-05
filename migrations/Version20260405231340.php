<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405231340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alertes_risques (id_alerte INT AUTO_INCREMENT NOT NULL, type_alerte VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, date_alerte DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, id_culture INT DEFAULT NULL, INDEX IDX_D7CFD2586834359B (id_culture), PRIMARY KEY (id_alerte)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cultures (id_culture INT AUTO_INCREMENT NOT NULL, nom_culture VARCHAR(100) NOT NULL, date_semis DATE NOT NULL, etat_croissance VARCHAR(50) DEFAULT NULL, rendement_prevu DOUBLE PRECISION DEFAULT NULL, id_parcelle INT DEFAULT NULL, owner_id INT DEFAULT NULL, INDEX IDX_2C605D6795B5C063 (id_parcelle), INDEX IDX_2C605D677E3C61F9 (owner_id), PRIMARY KEY (id_culture)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE historique_cultures (id_historique INT AUTO_INCREMENT NOT NULL, ancienne_culture VARCHAR(100) DEFAULT NULL, date_recolte_effective DATE DEFAULT NULL, rendement_final DOUBLE PRECISION DEFAULT NULL, id_parcelle INT DEFAULT NULL, INDEX IDX_ECB8259595B5C063 (id_parcelle), PRIMARY KEY (id_historique)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE historique_irrigation (id INT AUTO_INCREMENT NOT NULL, date_irrigation DATETIME NOT NULL, duree_minutes INT NOT NULL, volume_eau NUMERIC(10, 2) DEFAULT NULL, humidite_avant NUMERIC(10, 2) DEFAULT NULL, type_declenchement VARCHAR(255) DEFAULT NULL, id_systeme INT DEFAULT NULL, INDEX IDX_C2B514E14BE4C493 (id_systeme), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mouvement_stock (id_mouvement INT AUTO_INCREMENT NOT NULL, type_mouvement VARCHAR(255) NOT NULL, date_mouvement VARCHAR(255) NOT NULL, quantite INT NOT NULL, motif VARCHAR(255) NOT NULL, id_produit INT NOT NULL, id_user INT NOT NULL, PRIMARY KEY (id_mouvement)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE parcelles (id_parcelle INT AUTO_INCREMENT NOT NULL, nom_parcelle VARCHAR(100) NOT NULL, surface DOUBLE PRECISION NOT NULL, coordonnees_gps VARCHAR(255) DEFAULT NULL, type_sol VARCHAR(50) DEFAULT NULL, owner_id INT DEFAULT NULL, INDEX IDX_4F15F60E7E3C61F9 (owner_id), PRIMARY KEY (id_parcelle)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE produit (id_produit INT AUTO_INCREMENT NOT NULL, nom_produit VARCHAR(255) NOT NULL, categorie VARCHAR(255) NOT NULL, quantite_disponible INT NOT NULL, unite VARCHAR(255) NOT NULL, seuil_alerte INT NOT NULL, date_expiration VARCHAR(255) DEFAULT NULL, prix_unitaire INT NOT NULL, PRIMARY KEY (id_produit)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE systeme_irrigation (id_systeme INT AUTO_INCREMENT NOT NULL, id_parcelle INT NOT NULL, nom_systeme VARCHAR(255) NOT NULL, seuil_humidite NUMERIC(10, 2) DEFAULT NULL, mode VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) DEFAULT NULL, date_creation DATETIME NOT NULL, PRIMARY KEY (id_systeme)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tache (id INT AUTO_INCREMENT NOT NULL, tittre_tache VARCHAR(255) NOT NULL, description_tache VARCHAR(255) NOT NULL, type_tache VARCHAR(255) NOT NULL, id_user INT NOT NULL, date_tache DATE NOT NULL, heure_debut_tache TIME NOT NULL, heure_fin_tache TIME NOT NULL, status_tache VARCHAR(255) NOT NULL, remarque_tache VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id_user INT AUTO_INCREMENT NOT NULL, nom_user VARCHAR(255) NOT NULL, prenom_user VARCHAR(255) NOT NULL, email_user VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, role_user VARCHAR(255) NOT NULL, num_user INT NOT NULL, adresse_user VARCHAR(255) NOT NULL, photo_path VARCHAR(255) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, reset_token VARCHAR(20) DEFAULT NULL, reset_expires DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D64912A5F6CC (email_user), PRIMARY KEY (id_user)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE alertes_risques ADD CONSTRAINT FK_D7CFD2586834359B FOREIGN KEY (id_culture) REFERENCES cultures (id_culture)');
        $this->addSql('ALTER TABLE cultures ADD CONSTRAINT FK_2C605D6795B5C063 FOREIGN KEY (id_parcelle) REFERENCES parcelles (id_parcelle) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cultures ADD CONSTRAINT FK_2C605D677E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE historique_cultures ADD CONSTRAINT FK_ECB8259595B5C063 FOREIGN KEY (id_parcelle) REFERENCES parcelles (id_parcelle) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE historique_irrigation ADD CONSTRAINT FK_C2B514E14BE4C493 FOREIGN KEY (id_systeme) REFERENCES systeme_irrigation (id_systeme)');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT FK_4F15F60E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alertes_risques DROP FOREIGN KEY FK_D7CFD2586834359B');
        $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY FK_2C605D6795B5C063');
        $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY FK_2C605D677E3C61F9');
        $this->addSql('ALTER TABLE historique_cultures DROP FOREIGN KEY FK_ECB8259595B5C063');
        $this->addSql('ALTER TABLE historique_irrigation DROP FOREIGN KEY FK_C2B514E14BE4C493');
        $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY FK_4F15F60E7E3C61F9');
        $this->addSql('DROP TABLE alertes_risques');
        $this->addSql('DROP TABLE cultures');
        $this->addSql('DROP TABLE historique_cultures');
        $this->addSql('DROP TABLE historique_irrigation');
        $this->addSql('DROP TABLE mouvement_stock');
        $this->addSql('DROP TABLE parcelles');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE systeme_irrigation');
        $this->addSql('DROP TABLE tache');
        $this->addSql('DROP TABLE user');
    }
}
