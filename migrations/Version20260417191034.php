<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417191034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cultures ADD CONSTRAINT FK_2C605D6795B5C063 FOREIGN KEY (id_parcelle) REFERENCES parcelles (id_parcelle) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE historique_irrigation ADD CONSTRAINT FK_C2B514E14BE4C493 FOREIGN KEY (id_systeme) REFERENCES systeme_irrigation (id_systeme)');
        $this->addSql('DROP INDEX idx_systeme_historique ON historique_irrigation');
        $this->addSql('CREATE INDEX IDX_C2B514E14BE4C493 ON historique_irrigation (id_systeme)');
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
        $this->addSql('DROP INDEX fk_produit_owner ON produit');
        $this->addSql('CREATE INDEX IDX_29A5EC277E3C61F9 ON produit (owner_id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT `FK_PRODUIT_OWNER` FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_commentaire ADD date_commentaire DATE NOT NULL');
        $this->addSql('DROP INDEX idx_parcelle ON systeme_irrigation');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE id_systeme id_systeme INT AUTO_INCREMENT NOT NULL, CHANGE id_parcelle id_parcelle INT NOT NULL, CHANGE nom_systeme nom_systeme VARCHAR(255) NOT NULL, CHANGE seuil_humidite seuil_humidite NUMERIC(10, 2) DEFAULT NULL, CHANGE mode mode VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(255) NOT NULL, CHANGE description_tache description_tache VARCHAR(255) NOT NULL, CHANGE type_tache type_tache VARCHAR(255) NOT NULL, CHANGE status_tache status_tache VARCHAR(255) NOT NULL, CHANGE remarque_tache remarque_tache VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE reset_token reset_token VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64912A5F6CC ON user (email_user)');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY `FK_VENTE_RECOLTE`');
        $this->addSql('ALTER TABLE vente DROP price, DROP sale_date, CHANGE description description VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX idx_vente_recolte ON vente');
        $this->addSql('CREATE INDEX IDX_888A2A4CC2C4F051 ON vente (recolte_id)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT `FK_VENTE_RECOLTE` FOREIGN KEY (recolte_id) REFERENCES recolte (id_recolte) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY FK_2C605D6795B5C063');
        $this->addSql('ALTER TABLE historique_irrigation DROP FOREIGN KEY FK_C2B514E14BE4C493');
        $this->addSql('ALTER TABLE historique_irrigation DROP FOREIGN KEY FK_C2B514E14BE4C493');
        $this->addSql('DROP INDEX idx_c2b514e14be4c493 ON historique_irrigation');
        $this->addSql('CREATE INDEX idx_systeme_historique ON historique_irrigation (id_systeme)');
        $this->addSql('ALTER TABLE historique_irrigation ADD CONSTRAINT FK_C2B514E14BE4C493 FOREIGN KEY (id_systeme) REFERENCES systeme_irrigation (id_systeme)');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EBF7384557');
        $this->addSql('DROP INDEX IDX_61E2C8EBF7384557 ON mouvement_stock');
        $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY FK_4F15F60E7E3C61F9');
        $this->addSql('ALTER TABLE parcelles ADD id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT `FK_PARCELLE_USER` FOREIGN KEY (id_user) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX FK_PARCELLE_USER ON parcelles (id_user)');
        $this->addSql('DROP INDEX idx_4f15f60e7e3c61f9 ON parcelles');
        $this->addSql('CREATE INDEX FK_PARCELLE_OWNER ON parcelles (owner_id)');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT FK_4F15F60E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC277E3C61F9');
        $this->addSql('DROP INDEX idx_29a5ec277e3c61f9 ON produit');
        $this->addSql('CREATE INDEX FK_PRODUIT_OWNER ON produit (owner_id)');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC277E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_commentaire DROP date_commentaire');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE id_systeme id_systeme BIGINT AUTO_INCREMENT NOT NULL, CHANGE id_parcelle id_parcelle BIGINT NOT NULL, CHANGE nom_systeme nom_systeme VARCHAR(100) NOT NULL, CHANGE seuil_humidite seuil_humidite NUMERIC(5, 2) DEFAULT \'30.00\', CHANGE mode mode ENUM(\'AUTO\', \'MANUEL\') DEFAULT \'MANUEL\', CHANGE statut statut ENUM(\'ACTIF\', \'INACTIF\') DEFAULT \'ACTIF\', CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE INDEX idx_parcelle ON systeme_irrigation (id_parcelle)');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(150) NOT NULL, CHANGE description_tache description_tache VARCHAR(200) NOT NULL, CHANGE type_tache type_tache VARCHAR(200) NOT NULL, CHANGE status_tache status_tache VARCHAR(200) NOT NULL, CHANGE remarque_tache remarque_tache VARCHAR(200) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_8D93D64912A5F6CC ON user');
        $this->addSql('ALTER TABLE user CHANGE reset_token reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CC2C4F051');
        $this->addSql('ALTER TABLE vente ADD price NUMERIC(10, 2) DEFAULT NULL, ADD sale_date DATE DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_888a2a4cc2c4f051 ON vente');
        $this->addSql('CREATE INDEX IDX_VENTE_RECOLTE ON vente (recolte_id)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CC2C4F051 FOREIGN KEY (recolte_id) REFERENCES recolte (id_recolte) ON DELETE SET NULL');
    }
}
