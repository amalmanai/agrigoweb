<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417045447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE historique_irrigation CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE id_systeme id_systeme INT DEFAULT NULL, CHANGE date_irrigation date_irrigation DATETIME NOT NULL, CHANGE volume_eau volume_eau NUMERIC(10, 2) DEFAULT NULL, CHANGE humidite_avant humidite_avant NUMERIC(10, 2) DEFAULT NULL, CHANGE type_declenchement type_declenchement VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE historique_irrigation ADD CONSTRAINT FK_C2B514E14BE4C493 FOREIGN KEY (id_systeme) REFERENCES systeme_irrigation (id_systeme)');
        $this->addSql('DROP INDEX idx_systeme_historique ON historique_irrigation');
        $this->addSql('CREATE INDEX IDX_C2B514E14BE4C493 ON historique_irrigation (id_systeme)');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE date_mouvement date_mouvement VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY `FK_PARCELLE_USER`');
        $this->addSql('DROP INDEX FK_PARCELLE_USER ON parcelles');
        $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY `FK_PARCELLE_OWNER`');
        $this->addSql('ALTER TABLE parcelles DROP id_user');
        $this->addSql('DROP INDEX fk_parcelle_owner ON parcelles');
        $this->addSql('CREATE INDEX IDX_4F15F60E7E3C61F9 ON parcelles (owner_id)');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT `FK_PARCELLE_OWNER` FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit CHANGE id_produit id_produit INT AUTO_INCREMENT NOT NULL, CHANGE unite unite VARCHAR(255) NOT NULL, CHANGE date_expiration date_expiration VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_parcelle ON systeme_irrigation');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE id_systeme id_systeme INT AUTO_INCREMENT NOT NULL, CHANGE id_parcelle id_parcelle INT NOT NULL, CHANGE nom_systeme nom_systeme VARCHAR(255) NOT NULL, CHANGE seuil_humidite seuil_humidite NUMERIC(10, 2) DEFAULT NULL, CHANGE mode mode VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(255) NOT NULL, CHANGE description_tache description_tache VARCHAR(255) NOT NULL, CHANGE type_tache type_tache VARCHAR(255) NOT NULL, CHANGE status_tache status_tache VARCHAR(255) NOT NULL, CHANGE remarque_tache remarque_tache VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT NOT NULL, ADD verification_token VARCHAR(255) DEFAULT NULL, ADD verification_token_expires_at DATETIME DEFAULT NULL, CHANGE reset_token reset_token VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64912A5F6CC ON user (email_user)');
        $this->addSql('ALTER TABLE vente DROP id_user, DROP price, DROP saleDate');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CC2C4F051 FOREIGN KEY (recolte_id) REFERENCES recolte (id_recolte) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_888A2A4CC2C4F051 ON vente (recolte_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE historique_irrigation DROP FOREIGN KEY FK_C2B514E14BE4C493');
        $this->addSql('ALTER TABLE historique_irrigation DROP FOREIGN KEY FK_C2B514E14BE4C493');
        $this->addSql('ALTER TABLE historique_irrigation CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE date_irrigation date_irrigation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE volume_eau volume_eau NUMERIC(8, 2) DEFAULT NULL, CHANGE humidite_avant humidite_avant NUMERIC(5, 2) DEFAULT NULL, CHANGE type_declenchement type_declenchement ENUM(\'AUTO\', \'MANUEL\') DEFAULT \'MANUEL\', CHANGE id_systeme id_systeme BIGINT NOT NULL');
        $this->addSql('DROP INDEX idx_c2b514e14be4c493 ON historique_irrigation');
        $this->addSql('CREATE INDEX idx_systeme_historique ON historique_irrigation (id_systeme)');
        $this->addSql('ALTER TABLE historique_irrigation ADD CONSTRAINT FK_C2B514E14BE4C493 FOREIGN KEY (id_systeme) REFERENCES systeme_irrigation (id_systeme)');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE date_mouvement date_mouvement VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY FK_4F15F60E7E3C61F9');
        $this->addSql('ALTER TABLE parcelles ADD id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT `FK_PARCELLE_USER` FOREIGN KEY (id_user) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX FK_PARCELLE_USER ON parcelles (id_user)');
        $this->addSql('DROP INDEX idx_4f15f60e7e3c61f9 ON parcelles');
        $this->addSql('CREATE INDEX FK_PARCELLE_OWNER ON parcelles (owner_id)');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT FK_4F15F60E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit CHANGE id_produit id_produit INT NOT NULL, CHANGE unite unite VARCHAR(50) NOT NULL, CHANGE date_expiration date_expiration VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE id_systeme id_systeme BIGINT AUTO_INCREMENT NOT NULL, CHANGE id_parcelle id_parcelle BIGINT NOT NULL, CHANGE nom_systeme nom_systeme VARCHAR(100) NOT NULL, CHANGE seuil_humidite seuil_humidite NUMERIC(5, 2) DEFAULT \'30.00\', CHANGE mode mode ENUM(\'AUTO\', \'MANUEL\') DEFAULT \'MANUEL\', CHANGE statut statut ENUM(\'ACTIF\', \'INACTIF\') DEFAULT \'ACTIF\', CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE INDEX idx_parcelle ON systeme_irrigation (id_parcelle)');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(150) NOT NULL, CHANGE description_tache description_tache VARCHAR(200) NOT NULL, CHANGE type_tache type_tache VARCHAR(200) NOT NULL, CHANGE status_tache status_tache VARCHAR(200) NOT NULL, CHANGE remarque_tache remarque_tache VARCHAR(200) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_8D93D64912A5F6CC ON user');
        $this->addSql('ALTER TABLE user DROP is_verified, DROP verification_token, DROP verification_token_expires_at, CHANGE reset_token reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CC2C4F051');
        $this->addSql('DROP INDEX IDX_888A2A4CC2C4F051 ON vente');
        $this->addSql('ALTER TABLE vente ADD id_user INT NOT NULL, ADD price NUMERIC(10, 2) DEFAULT NULL, ADD saleDate DATE DEFAULT NULL');
    }
}
