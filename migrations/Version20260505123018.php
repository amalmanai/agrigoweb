<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505123018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY `FK_FAB3FC1654177093`');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY `FK_FAB3FC16F624B39D`');
        $this->addSql('ALTER TABLE chat_room_participants DROP FOREIGN KEY `FK_223BBAD854177093`');
        $this->addSql('ALTER TABLE chat_room_participants DROP FOREIGN KEY `FK_223BBAD8A76ED395`');
        $this->addSql('DROP TABLE chat_message');
        $this->addSql('DROP TABLE chat_room');
        $this->addSql('DROP TABLE chat_room_participants');
        $this->addSql('ALTER TABLE alertes_risques DROP FOREIGN KEY `FK_D7CFD2586834359B`');
        $this->addSql('DROP INDEX IDX_D7CFD2586834359B ON alertes_risques');
        $this->addSql('ALTER TABLE alertes_risques CHANGE id_culture culture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE alertes_risques ADD CONSTRAINT FK_D7CFD258B108249D FOREIGN KEY (culture_id) REFERENCES cultures (id_culture)');
        $this->addSql('CREATE INDEX IDX_D7CFD258B108249D ON alertes_risques (culture_id)');
        $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY `FK_2C605D6795B5C063`');
        $this->addSql('DROP INDEX IDX_2C605D6795B5C063 ON cultures');
        $this->addSql('ALTER TABLE cultures CHANGE id_parcelle parcelle_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cultures ADD CONSTRAINT FK_2C605D674433ED66 FOREIGN KEY (parcelle_id) REFERENCES parcelles (id_parcelle) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2C605D674433ED66 ON cultures (parcelle_id)');
        $this->addSql('ALTER TABLE historique_cultures DROP FOREIGN KEY `historique_cultures_ibfk_1`');
        $this->addSql('DROP INDEX IDX_ECB8259595B5C063 ON historique_cultures');
        $this->addSql('ALTER TABLE historique_cultures CHANGE id_parcelle parcelle_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE historique_cultures ADD CONSTRAINT FK_ECB825954433ED66 FOREIGN KEY (parcelle_id) REFERENCES parcelles (id_parcelle) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_ECB825954433ED66 ON historique_cultures (parcelle_id)');
        $this->addSql('ALTER TABLE historique_irrigation DROP FOREIGN KEY `FK_C2B514E14BE4C493`');
        $this->addSql('DROP INDEX IDX_C2B514E14BE4C493 ON historique_irrigation');
        $this->addSql('ALTER TABLE historique_irrigation CHANGE id_systeme systeme_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE historique_irrigation ADD CONSTRAINT FK_C2B514E1346F772E FOREIGN KEY (systeme_id) REFERENCES systeme_irrigation (id_systeme)');
        $this->addSql('CREATE INDEX IDX_C2B514E1346F772E ON historique_irrigation (systeme_id)');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY `FK_61E2C8EBF7384557`');
        $this->addSql('DROP INDEX IDX_61E2C8EBF7384557 ON mouvement_stock');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE id_produit produit_id INT NOT NULL');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_61E2C8EBF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit)');
        $this->addSql('CREATE INDEX IDX_61E2C8EBF347EFB ON mouvement_stock (produit_id)');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY `FK_19E82C386B3CA4B`');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY `FK_19E82C38F7384557`');
        $this->addSql('DROP INDEX IDX_19E82C386B3CA4B ON produit_commentaire');
        $this->addSql('DROP INDEX IDX_19E82C38F7384557 ON produit_commentaire');
        $this->addSql('DELETE FROM produit_commentaire WHERE id_produit NOT IN (SELECT id_produit FROM produit)');
        $this->addSql('DELETE FROM produit_commentaire WHERE id_user NOT IN (SELECT id_user FROM user)');
        $this->addSql('ALTER TABLE produit_commentaire ADD produit_id INT NOT NULL, ADD user_id INT NOT NULL, DROP id_produit, DROP id_user');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38A76ED395 FOREIGN KEY (user_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_19E82C38F347EFB ON produit_commentaire (produit_id)');
        $this->addSql('CREATE INDEX IDX_19E82C38A76ED395 ON produit_commentaire (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chat_message (id_message INT AUTO_INCREMENT NOT NULL, content LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, file_path VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, file_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, file_type VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, file_size INT DEFAULT NULL, message_type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, is_read TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, room_id INT NOT NULL, sender_id INT NOT NULL, INDEX IDX_FAB3FC1654177093 (room_id), INDEX IDX_FAB3FC16F624B39D (sender_id), PRIMARY KEY (id_message)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE chat_room (id_room INT AUTO_INCREMENT NOT NULL, nom_room VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, type_room VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, jitsi_room_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D403CCDA4158FBD9 (jitsi_room_name), PRIMARY KEY (id_room)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE chat_room_participants (room_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_223BBAD8A76ED395 (user_id), INDEX IDX_223BBAD854177093 (room_id), PRIMARY KEY (room_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT `FK_FAB3FC1654177093` FOREIGN KEY (room_id) REFERENCES chat_room (id_room) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT `FK_FAB3FC16F624B39D` FOREIGN KEY (sender_id) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE chat_room_participants ADD CONSTRAINT `FK_223BBAD854177093` FOREIGN KEY (room_id) REFERENCES chat_room (id_room)');
        $this->addSql('ALTER TABLE chat_room_participants ADD CONSTRAINT `FK_223BBAD8A76ED395` FOREIGN KEY (user_id) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE alertes_risques DROP FOREIGN KEY FK_D7CFD258B108249D');
        $this->addSql('DROP INDEX IDX_D7CFD258B108249D ON alertes_risques');
        $this->addSql('ALTER TABLE alertes_risques CHANGE culture_id id_culture INT DEFAULT NULL');
        $this->addSql('ALTER TABLE alertes_risques ADD CONSTRAINT `FK_D7CFD2586834359B` FOREIGN KEY (id_culture) REFERENCES cultures (id_culture)');
        $this->addSql('CREATE INDEX IDX_D7CFD2586834359B ON alertes_risques (id_culture)');
        $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY FK_2C605D674433ED66');
        $this->addSql('DROP INDEX IDX_2C605D674433ED66 ON cultures');
        $this->addSql('ALTER TABLE cultures CHANGE parcelle_id id_parcelle INT DEFAULT NULL');
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
        $this->addSql('ALTER TABLE historique_irrigation ADD CONSTRAINT `FK_C2B514E14BE4C493` FOREIGN KEY (id_systeme) REFERENCES systeme_irrigation (id_systeme)');
        $this->addSql('CREATE INDEX IDX_C2B514E14BE4C493 ON historique_irrigation (id_systeme)');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EBF347EFB');
        $this->addSql('DROP INDEX IDX_61E2C8EBF347EFB ON mouvement_stock');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE produit_id id_produit INT NOT NULL');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT `FK_61E2C8EBF7384557` FOREIGN KEY (id_produit) REFERENCES produit (id_produit)');
        $this->addSql('CREATE INDEX IDX_61E2C8EBF7384557 ON mouvement_stock (id_produit)');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38F347EFB');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38A76ED395');
        $this->addSql('DROP INDEX IDX_19E82C38F347EFB ON produit_commentaire');
        $this->addSql('DROP INDEX IDX_19E82C38A76ED395 ON produit_commentaire');
        $this->addSql('ALTER TABLE produit_commentaire ADD id_produit INT NOT NULL, ADD id_user INT NOT NULL, DROP produit_id, DROP user_id');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT `FK_19E82C386B3CA4B` FOREIGN KEY (id_user) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT `FK_19E82C38F7384557` FOREIGN KEY (id_produit) REFERENCES produit (id_produit) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_19E82C386B3CA4B ON produit_commentaire (id_user)');
        $this->addSql('CREATE INDEX IDX_19E82C38F7384557 ON produit_commentaire (id_produit)');
    }
}
