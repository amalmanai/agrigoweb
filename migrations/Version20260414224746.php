<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414224746 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE produit_commentaire (id_commentaire INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_commentaire DATETIME NOT NULL, id_produit INT NOT NULL, id_user INT NOT NULL, INDEX IDX_19E82C38F7384557 (id_produit), INDEX IDX_19E82C386B3CA4B (id_user), PRIMARY KEY (id_commentaire)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38F7384557 FOREIGN KEY (id_produit) REFERENCES produit (id_produit) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C386B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE historique_irrigation CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE id_systeme id_systeme INT DEFAULT NULL, CHANGE date_irrigation date_irrigation DATETIME NOT NULL, CHANGE volume_eau volume_eau NUMERIC(10, 2) DEFAULT NULL, CHANGE humidite_avant humidite_avant NUMERIC(10, 2) DEFAULT NULL, CHANGE type_declenchement type_declenchement VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE historique_irrigation ADD CONSTRAINT FK_C2B514E14BE4C493 FOREIGN KEY (id_systeme) REFERENCES systeme_irrigation (id_systeme)');
        $this->addSql('DROP INDEX idx_systeme_historique ON historique_irrigation');
        $this->addSql('CREATE INDEX IDX_C2B514E14BE4C493 ON historique_irrigation (id_systeme)');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE date_mouvement date_mouvement VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_61E2C8EBF7384557 FOREIGN KEY (id_produit) REFERENCES produit (id_produit)');
        $this->addSql('CREATE INDEX IDX_61E2C8EBF7384557 ON mouvement_stock (id_produit)');
        $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY `FK_PARCELLE_USER`');
        $this->addSql('DROP INDEX FK_PARCELLE_USER ON parcelles');
        $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY `FK_PARCELLE_OWNER`');
        $this->addSql('ALTER TABLE parcelles DROP id_user');
        $this->addSql('DROP INDEX fk_parcelle_owner ON parcelles');
        $this->addSql('CREATE INDEX IDX_4F15F60E7E3C61F9 ON parcelles (owner_id)');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT `FK_PARCELLE_OWNER` FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY `FK_PRODUIT_OWNER`');
        $this->addSql('ALTER TABLE produit ADD commentaire LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX fk_produit_owner ON produit');
        $this->addSql('CREATE INDEX IDX_29A5EC277E3C61F9 ON produit (owner_id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT `FK_PRODUIT_OWNER` FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_parcelle ON systeme_irrigation');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE id_systeme id_systeme INT AUTO_INCREMENT NOT NULL, CHANGE id_parcelle id_parcelle INT NOT NULL, CHANGE nom_systeme nom_systeme VARCHAR(255) NOT NULL, CHANGE seuil_humidite seuil_humidite NUMERIC(10, 2) DEFAULT NULL, CHANGE mode mode VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(255) NOT NULL, CHANGE description_tache description_tache VARCHAR(255) NOT NULL, CHANGE type_tache type_tache VARCHAR(255) NOT NULL, CHANGE status_tache status_tache VARCHAR(255) NOT NULL, CHANGE remarque_tache remarque_tache VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE reset_token reset_token VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64912A5F6CC ON user (email_user)');
        $this->addSql('ALTER TABLE vente DROP id_user, DROP price, DROP saleDate');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CC2C4F051 FOREIGN KEY (recolte_id) REFERENCES recolte (id_recolte) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_888A2A4CC2C4F051 ON vente (recolte_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38F7384557');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C386B3CA4B');
        $this->addSql('DROP TABLE produit_commentaire');
        $this->addSql('ALTER TABLE historique_irrigation DROP FOREIGN KEY FK_C2B514E14BE4C493');
        $this->addSql('ALTER TABLE historique_irrigation DROP FOREIGN KEY FK_C2B514E14BE4C493');
        $this->addSql('ALTER TABLE historique_irrigation CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE date_irrigation date_irrigation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE volume_eau volume_eau NUMERIC(8, 2) DEFAULT NULL, CHANGE humidite_avant humidite_avant NUMERIC(5, 2) DEFAULT NULL, CHANGE type_declenchement type_declenchement ENUM(\'AUTO\', \'MANUEL\') DEFAULT \'MANUEL\', CHANGE id_systeme id_systeme BIGINT NOT NULL');
        $this->addSql('DROP INDEX idx_c2b514e14be4c493 ON historique_irrigation');
        $this->addSql('CREATE INDEX idx_systeme_historique ON historique_irrigation (id_systeme)');
        $this->addSql('ALTER TABLE historique_irrigation ADD CONSTRAINT FK_C2B514E14BE4C493 FOREIGN KEY (id_systeme) REFERENCES systeme_irrigation (id_systeme)');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EBF7384557');
        $this->addSql('DROP INDEX IDX_61E2C8EBF7384557 ON mouvement_stock');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE date_mouvement date_mouvement VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY FK_4F15F60E7E3C61F9');
        $this->addSql('ALTER TABLE parcelles ADD id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT `FK_PARCELLE_USER` FOREIGN KEY (id_user) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX FK_PARCELLE_USER ON parcelles (id_user)');
        $this->addSql('DROP INDEX idx_4f15f60e7e3c61f9 ON parcelles');
        $this->addSql('CREATE INDEX FK_PARCELLE_OWNER ON parcelles (owner_id)');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT FK_4F15F60E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC277E3C61F9');
        $this->addSql('ALTER TABLE produit DROP commentaire');
        $this->addSql('DROP INDEX idx_29a5ec277e3c61f9 ON produit');
        $this->addSql('CREATE INDEX FK_PRODUIT_OWNER ON produit (owner_id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC277E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE id_systeme id_systeme BIGINT AUTO_INCREMENT NOT NULL, CHANGE id_parcelle id_parcelle BIGINT NOT NULL, CHANGE nom_systeme nom_systeme VARCHAR(100) NOT NULL, CHANGE seuil_humidite seuil_humidite NUMERIC(5, 2) DEFAULT \'30.00\', CHANGE mode mode ENUM(\'AUTO\', \'MANUEL\') DEFAULT \'MANUEL\', CHANGE statut statut ENUM(\'ACTIF\', \'INACTIF\') DEFAULT \'ACTIF\', CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE INDEX idx_parcelle ON systeme_irrigation (id_parcelle)');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(150) NOT NULL, CHANGE description_tache description_tache VARCHAR(200) NOT NULL, CHANGE type_tache type_tache VARCHAR(200) NOT NULL, CHANGE status_tache status_tache VARCHAR(200) NOT NULL, CHANGE remarque_tache remarque_tache VARCHAR(200) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_8D93D64912A5F6CC ON user');
        $this->addSql('ALTER TABLE user CHANGE reset_token reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CC2C4F051');
        $this->addSql('DROP INDEX IDX_888A2A4CC2C4F051 ON vente');
        $this->addSql('ALTER TABLE vente ADD id_user INT NOT NULL, ADD price NUMERIC(10, 2) DEFAULT NULL, ADD saleDate DATE DEFAULT NULL');
    }
}
