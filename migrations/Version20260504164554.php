<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260504164554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alertes_risques DROP FOREIGN KEY `FK_D7CFD2586834359B`');
        $this->addSql('DROP INDEX IDX_D7CFD2586834359B ON alertes_risques');
        $this->addSql('ALTER TABLE alertes_risques CHANGE id_culture culture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE alertes_risques ADD CONSTRAINT FK_D7CFD258B108249D FOREIGN KEY (culture_id) REFERENCES cultures (id_culture)');
        $this->addSql('CREATE INDEX IDX_D7CFD258B108249D ON alertes_risques (culture_id)');
        $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY `FK_2C605D6795B5C063`');
        $this->addSql('DROP INDEX IDX_2C605D6795B5C063 ON cultures');
        $this->addSql('ALTER TABLE cultures ADD parcelle_id INT NOT NULL, DROP id_parcelle');
        $this->addSql('ALTER TABLE cultures ADD CONSTRAINT FK_2C605D674433ED66 FOREIGN KEY (parcelle_id) REFERENCES parcelles (id_parcelle) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2C605D674433ED66 ON cultures (parcelle_id)');
        $this->addSql('ALTER TABLE historique_cultures DROP FOREIGN KEY `historique_cultures_ibfk_1`');
        $this->addSql('DROP INDEX IDX_ECB8259595B5C063 ON historique_cultures');
        $this->addSql('ALTER TABLE historique_cultures CHANGE id_parcelle parcelle_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE historique_cultures ADD CONSTRAINT FK_ECB825954433ED66 FOREIGN KEY (parcelle_id) REFERENCES parcelles (id_parcelle) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_ECB825954433ED66 ON historique_cultures (parcelle_id)');
        $this->addSql('DROP INDEX idx_systeme_historique ON historique_irrigation');
        $this->addSql('ALTER TABLE historique_irrigation CHANGE id_systeme systeme_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE historique_irrigation ADD CONSTRAINT FK_C2B514E1346F772E FOREIGN KEY (systeme_id) REFERENCES systeme_irrigation (id_systeme)');
        $this->addSql('CREATE INDEX IDX_C2B514E1346F772E ON historique_irrigation (systeme_id)');
        $this->addSql('ALTER TABLE marketplace_order DROP FOREIGN KEY `FK_378C3E6A7E0EAFA3`');
        $this->addSql('ALTER TABLE marketplace_order DROP FOREIGN KEY `FK_378C3E6A8A98A7A5`');
        $this->addSql('ALTER TABLE marketplace_order CHANGE ordered_at ordered_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX idx_378c3e6a7e0eafa3 ON marketplace_order');
        $this->addSql('CREATE INDEX IDX_51EA2CDF7DC7170A ON marketplace_order (vente_id)');
        $this->addSql('DROP INDEX idx_378c3e6a8a98a7a5 ON marketplace_order');
        $this->addSql('CREATE INDEX IDX_51EA2CDF6C755722 ON marketplace_order (buyer_id)');
        $this->addSql('ALTER TABLE marketplace_order ADD CONSTRAINT `FK_378C3E6A7E0EAFA3` FOREIGN KEY (vente_id) REFERENCES vente (id_vente) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_order ADD CONSTRAINT `FK_378C3E6A8A98A7A5` FOREIGN KEY (buyer_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE date_mouvement date_mouvement DATETIME NOT NULL, CHANGE id_produit produit_id INT NOT NULL');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_61E2C8EBF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit)');
        $this->addSql('CREATE INDEX IDX_61E2C8EBF347EFB ON mouvement_stock (produit_id)');
        $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY `FK_PARCELLE_OWNER`');
        $this->addSql('DROP INDEX fk_parcelle_owner ON parcelles');
        $this->addSql('CREATE INDEX IDX_4F15F60E7E3C61F9 ON parcelles (owner_id)');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT `FK_PARCELLE_OWNER` FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit CHANGE unite unite VARCHAR(255) NOT NULL, CHANGE date_expiration date_expiration DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY `FK_19E82C386B3CA4B`');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY `FK_19E82C38F7384557`');
        $this->addSql('DROP INDEX IDX_19E82C38F7384557 ON produit_commentaire');
        $this->addSql('DROP INDEX IDX_19E82C386B3CA4B ON produit_commentaire');
        $this->addSql('ALTER TABLE produit_commentaire ADD produit_id INT NOT NULL, ADD user_id INT NOT NULL, DROP id_produit, DROP id_user');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38A76ED395 FOREIGN KEY (user_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_19E82C38F347EFB ON produit_commentaire (produit_id)');
        $this->addSql('CREATE INDEX IDX_19E82C38A76ED395 ON produit_commentaire (user_id)');
        $this->addSql('ALTER TABLE recolte DROP FOREIGN KEY `FK_2E02095895B5C063`');
        $this->addSql('DROP INDEX IDX_2E02095895B5C063 ON recolte');
        $this->addSql('ALTER TABLE recolte ADD user_id INT DEFAULT NULL, DROP id_user, DROP adresse, DROP parcelle_id, CHANGE cout_production cout_production NUMERIC(12, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE recolte ADD CONSTRAINT FK_3433713CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3433713CA76ED395 ON recolte (user_id)');
        $this->addSql('DROP INDEX idx_parcelle ON systeme_irrigation');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE id_systeme id_systeme INT AUTO_INCREMENT NOT NULL, CHANGE id_parcelle id_parcelle INT NOT NULL, CHANGE nom_systeme nom_systeme VARCHAR(255) NOT NULL, CHANGE seuil_humidite seuil_humidite NUMERIC(10, 2) DEFAULT NULL, CHANGE mode mode VARCHAR(255) DEFAULT NULL, CHANGE statut statut VARCHAR(255) DEFAULT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(255) NOT NULL, CHANGE description_tache description_tache VARCHAR(255) NOT NULL, CHANGE type_tache type_tache VARCHAR(255) NOT NULL, CHANGE status_tache status_tache VARCHAR(255) NOT NULL, CHANGE remarque_tache remarque_tache VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user DROP is_verified, DROP verification_token, DROP verification_token_expires_at, DROP latitude, DROP longitude, DROP fcm_token, CHANGE reset_token reset_token VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64912A5F6CC ON user (email_user)');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY `FK_888A2A4C6C755722`');
        $this->addSql('DROP INDEX FK_888A2A4C6C755722 ON vente');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY `FK_VENTE_RECOLTE`');
        $this->addSql('ALTER TABLE vente DROP price, DROP saleDate, DROP rating, DROP rating_comment, DROP buyer_id, DROP delivery_location, DROP delivery_latitude, DROP delivery_longitude, DROP marketplace_listing, DROP available_quantity');
        $this->addSql('DROP INDEX fk_vente_recolte ON vente');
        $this->addSql('CREATE INDEX IDX_888A2A4CC2C4F051 ON vente (recolte_id)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT `FK_VENTE_RECOLTE` FOREIGN KEY (recolte_id) REFERENCES recolte (id_recolte) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE alertes_risques DROP FOREIGN KEY FK_D7CFD258B108249D');
        $this->addSql('DROP INDEX IDX_D7CFD258B108249D ON alertes_risques');
        $this->addSql('ALTER TABLE alertes_risques CHANGE culture_id id_culture INT DEFAULT NULL');
        $this->addSql('ALTER TABLE alertes_risques ADD CONSTRAINT `FK_D7CFD2586834359B` FOREIGN KEY (id_culture) REFERENCES cultures (id_culture)');
        $this->addSql('CREATE INDEX IDX_D7CFD2586834359B ON alertes_risques (id_culture)');
        $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY FK_2C605D674433ED66');
        $this->addSql('DROP INDEX IDX_2C605D674433ED66 ON cultures');
        $this->addSql('ALTER TABLE cultures ADD id_parcelle INT DEFAULT NULL, DROP parcelle_id');
        $this->addSql('ALTER TABLE cultures ADD CONSTRAINT `FK_2C605D6795B5C063` FOREIGN KEY (id_parcelle) REFERENCES parcelles (id_parcelle) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2C605D6795B5C063 ON cultures (id_parcelle)');
        $this->addSql('ALTER TABLE historique_cultures DROP FOREIGN KEY FK_ECB825954433ED66');
        $this->addSql('DROP INDEX IDX_ECB825954433ED66 ON historique_cultures');
        $this->addSql('ALTER TABLE historique_cultures CHANGE parcelle_id id_parcelle INT DEFAULT NULL');
        $this->addSql('ALTER TABLE historique_cultures ADD CONSTRAINT `historique_cultures_ibfk_1` FOREIGN KEY (id_parcelle) REFERENCES parcelles (id_parcelle) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_ECB8259595B5C063 ON historique_cultures (id_parcelle)');
        $this->addSql('ALTER TABLE historique_irrigation DROP FOREIGN KEY FK_C2B514E1346F772E');
        $this->addSql('DROP INDEX IDX_C2B514E1346F772E ON historique_irrigation');
        $this->addSql('ALTER TABLE historique_irrigation CHANGE systeme_id id_systeme INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_systeme_historique ON historique_irrigation (id_systeme)');
        $this->addSql('ALTER TABLE marketplace_order DROP FOREIGN KEY FK_51EA2CDF7DC7170A');
        $this->addSql('ALTER TABLE marketplace_order DROP FOREIGN KEY FK_51EA2CDF6C755722');
        $this->addSql('ALTER TABLE marketplace_order CHANGE ordered_at ordered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('DROP INDEX idx_51ea2cdf6c755722 ON marketplace_order');
        $this->addSql('CREATE INDEX IDX_378C3E6A8A98A7A5 ON marketplace_order (buyer_id)');
        $this->addSql('DROP INDEX idx_51ea2cdf7dc7170a ON marketplace_order');
        $this->addSql('CREATE INDEX IDX_378C3E6A7E0EAFA3 ON marketplace_order (vente_id)');
        $this->addSql('ALTER TABLE marketplace_order ADD CONSTRAINT FK_51EA2CDF7DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id_vente) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_order ADD CONSTRAINT FK_51EA2CDF6C755722 FOREIGN KEY (buyer_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EBF347EFB');
        $this->addSql('DROP INDEX IDX_61E2C8EBF347EFB ON mouvement_stock');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE date_mouvement date_mouvement VARCHAR(20) NOT NULL, CHANGE produit_id id_produit INT NOT NULL');
        $this->addSql('ALTER TABLE parcelles DROP FOREIGN KEY FK_4F15F60E7E3C61F9');
        $this->addSql('DROP INDEX idx_4f15f60e7e3c61f9 ON parcelles');
        $this->addSql('CREATE INDEX FK_PARCELLE_OWNER ON parcelles (owner_id)');
        $this->addSql('ALTER TABLE parcelles ADD CONSTRAINT FK_4F15F60E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE produit CHANGE unite unite VARCHAR(50) NOT NULL, CHANGE date_expiration date_expiration VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38F347EFB');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38A76ED395');
        $this->addSql('DROP INDEX IDX_19E82C38F347EFB ON produit_commentaire');
        $this->addSql('DROP INDEX IDX_19E82C38A76ED395 ON produit_commentaire');
        $this->addSql('ALTER TABLE produit_commentaire ADD id_produit INT NOT NULL, ADD id_user INT NOT NULL, DROP produit_id, DROP user_id');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT `FK_19E82C386B3CA4B` FOREIGN KEY (id_user) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT `FK_19E82C38F7384557` FOREIGN KEY (id_produit) REFERENCES produit (id_produit) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_19E82C38F7384557 ON produit_commentaire (id_produit)');
        $this->addSql('CREATE INDEX IDX_19E82C386B3CA4B ON produit_commentaire (id_user)');
        $this->addSql('ALTER TABLE recolte DROP FOREIGN KEY FK_3433713CA76ED395');
        $this->addSql('DROP INDEX IDX_3433713CA76ED395 ON recolte');
        $this->addSql('ALTER TABLE recolte ADD adresse VARCHAR(255) DEFAULT NULL, ADD parcelle_id INT DEFAULT NULL, CHANGE cout_production cout_production DOUBLE PRECISION DEFAULT NULL, CHANGE user_id id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recolte ADD CONSTRAINT `FK_2E02095895B5C063` FOREIGN KEY (parcelle_id) REFERENCES parcelles (id_parcelle) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_2E02095895B5C063 ON recolte (parcelle_id)');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE id_systeme id_systeme BIGINT AUTO_INCREMENT NOT NULL, CHANGE id_parcelle id_parcelle BIGINT NOT NULL, CHANGE nom_systeme nom_systeme VARCHAR(100) NOT NULL, CHANGE seuil_humidite seuil_humidite NUMERIC(5, 2) DEFAULT \'30.00\', CHANGE mode mode ENUM(\'AUTO\', \'MANUEL\') DEFAULT \'MANUEL\', CHANGE statut statut ENUM(\'ACTIF\', \'INACTIF\') DEFAULT \'ACTIF\', CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('CREATE INDEX idx_parcelle ON systeme_irrigation (id_parcelle)');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(150) NOT NULL, CHANGE description_tache description_tache VARCHAR(200) NOT NULL, CHANGE type_tache type_tache VARCHAR(200) NOT NULL, CHANGE status_tache status_tache VARCHAR(200) NOT NULL, CHANGE remarque_tache remarque_tache VARCHAR(200) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_8D93D64912A5F6CC ON user');
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT DEFAULT 0 NOT NULL, ADD verification_token VARCHAR(255) DEFAULT NULL, ADD verification_token_expires_at DATETIME DEFAULT NULL, ADD latitude DOUBLE PRECISION DEFAULT NULL, ADD longitude DOUBLE PRECISION DEFAULT NULL, ADD fcm_token VARCHAR(255) DEFAULT NULL, CHANGE reset_token reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CC2C4F051');
        $this->addSql('ALTER TABLE vente ADD price NUMERIC(10, 2) DEFAULT NULL, ADD saleDate DATE DEFAULT NULL, ADD rating SMALLINT DEFAULT NULL, ADD rating_comment LONGTEXT DEFAULT NULL, ADD buyer_id INT DEFAULT NULL, ADD delivery_location VARCHAR(500) DEFAULT NULL, ADD delivery_latitude VARCHAR(50) DEFAULT NULL, ADD delivery_longitude VARCHAR(50) DEFAULT NULL, ADD marketplace_listing TINYINT DEFAULT 1 NOT NULL, ADD available_quantity DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT `FK_888A2A4C6C755722` FOREIGN KEY (buyer_id) REFERENCES user (id_user) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX FK_888A2A4C6C755722 ON vente (buyer_id)');
        $this->addSql('DROP INDEX idx_888a2a4cc2c4f051 ON vente');
        $this->addSql('CREATE INDEX FK_VENTE_RECOLTE ON vente (recolte_id)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CC2C4F051 FOREIGN KEY (recolte_id) REFERENCES recolte (id_recolte) ON DELETE SET NULL');
    }
}
