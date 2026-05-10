<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505104438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE chat_message (id_message INT AUTO_INCREMENT NOT NULL, content LONGTEXT DEFAULT NULL, file_path VARCHAR(500) DEFAULT NULL, file_name VARCHAR(255) DEFAULT NULL, file_type VARCHAR(100) DEFAULT NULL, file_size INT DEFAULT NULL, message_type VARCHAR(50) NOT NULL, is_read TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, room_id INT NOT NULL, sender_id INT NOT NULL, INDEX IDX_FAB3FC1654177093 (room_id), INDEX IDX_FAB3FC16F624B39D (sender_id), PRIMARY KEY (id_message)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chat_room (id_room INT AUTO_INCREMENT NOT NULL, nom_room VARCHAR(255) DEFAULT NULL, type_room VARCHAR(50) NOT NULL, jitsi_room_name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D403CCDA4158FBD9 (jitsi_room_name), PRIMARY KEY (id_room)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE chat_room_participants (room_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_223BBAD854177093 (room_id), INDEX IDX_223BBAD8A76ED395 (user_id), PRIMARY KEY (room_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC1654177093 FOREIGN KEY (room_id) REFERENCES chat_room (id_room) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE chat_message ADD CONSTRAINT FK_FAB3FC16F624B39D FOREIGN KEY (sender_id) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE chat_room_participants ADD CONSTRAINT FK_223BBAD854177093 FOREIGN KEY (room_id) REFERENCES chat_room (id_room)');
        $this->addSql('ALTER TABLE chat_room_participants ADD CONSTRAINT FK_223BBAD8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY `FK_B6BD307FCD53EDB6`');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY `FK_B6BD307FF624B39D`');
        $this->addSql('DROP TABLE message');
        $this->addSql('ALTER TABLE alertes_risques CHANGE date_alerte date_alerte DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE cultures CHANGE nom_culture nom_culture VARCHAR(100) DEFAULT NULL, CHANGE date_semis date_semis DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE historique_irrigation CHANGE date_irrigation date_irrigation DATETIME DEFAULT NULL, CHANGE duree_minutes duree_minutes INT DEFAULT NULL');
        $this->addSql('ALTER TABLE marketplace_order CHANGE seller_id seller_id INT DEFAULT NULL, CHANGE quantity quantity DOUBLE PRECISION DEFAULT NULL, CHANGE unit_price unit_price NUMERIC(10, 2) DEFAULT NULL, CHANGE total_price total_price NUMERIC(10, 2) DEFAULT NULL, CHANGE status status VARCHAR(32) DEFAULT \'pending\'');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE type_mouvement type_mouvement VARCHAR(255) DEFAULT NULL, CHANGE date_mouvement date_mouvement DATETIME DEFAULT NULL, CHANGE quantite quantite INT DEFAULT NULL, CHANGE motif motif VARCHAR(255) DEFAULT NULL, CHANGE id_user id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE parcelles CHANGE nom_parcelle nom_parcelle VARCHAR(100) DEFAULT NULL, CHANGE surface surface DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE produit CHANGE nom_produit nom_produit VARCHAR(255) DEFAULT NULL, CHANGE categorie categorie VARCHAR(255) DEFAULT NULL, CHANGE quantite_disponible quantite_disponible INT DEFAULT NULL, CHANGE unite unite VARCHAR(255) DEFAULT NULL, CHANGE seuil_alerte seuil_alerte INT DEFAULT NULL, CHANGE prix_unitaire prix_unitaire INT DEFAULT NULL');
        $this->addSql('ALTER TABLE produit_commentaire CHANGE contenu contenu LONGTEXT DEFAULT NULL, CHANGE date_commentaire date_commentaire DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE recolte CHANGE cout_production cout_production NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE id_parcelle id_parcelle INT DEFAULT NULL, CHANGE nom_systeme nom_systeme VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(255) DEFAULT NULL, CHANGE description_tache description_tache VARCHAR(255) DEFAULT NULL, CHANGE type_tache type_tache VARCHAR(255) DEFAULT NULL, CHANGE id_user id_user INT DEFAULT NULL, CHANGE date_tache date_tache DATE DEFAULT NULL, CHANGE heure_debut_tache heure_debut_tache TIME DEFAULT NULL, CHANGE heure_fin_tache heure_fin_tache TIME DEFAULT NULL, CHANGE status_tache status_tache VARCHAR(255) DEFAULT NULL, CHANGE remarque_tache remarque_tache VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE nom_user nom_user VARCHAR(255) DEFAULT NULL, CHANGE email_user email_user VARCHAR(255) DEFAULT NULL, CHANGE role_user role_user VARCHAR(255) DEFAULT NULL, CHANGE num_user num_user INT DEFAULT NULL, CHANGE password password VARCHAR(255) DEFAULT NULL, CHANGE adresse_user adresse_user VARCHAR(255) DEFAULT NULL, CHANGE prenom_user prenom_user VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE vente CHANGE description description VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, is_read TINYINT NOT NULL, sender_id INT NOT NULL, receiver_id INT NOT NULL, media_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, media_type VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX IDX_B6BD307FCD53EDB6 (receiver_id), INDEX IDX_B6BD307FF624B39D (sender_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT `FK_B6BD307FCD53EDB6` FOREIGN KEY (receiver_id) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT `FK_B6BD307FF624B39D` FOREIGN KEY (sender_id) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC1654177093');
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16F624B39D');
        $this->addSql('ALTER TABLE chat_room_participants DROP FOREIGN KEY FK_223BBAD854177093');
        $this->addSql('ALTER TABLE chat_room_participants DROP FOREIGN KEY FK_223BBAD8A76ED395');
        $this->addSql('DROP TABLE chat_message');
        $this->addSql('DROP TABLE chat_room');
        $this->addSql('DROP TABLE chat_room_participants');
        $this->addSql('ALTER TABLE alertes_risques CHANGE date_alerte date_alerte DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE cultures CHANGE nom_culture nom_culture VARCHAR(100) NOT NULL, CHANGE date_semis date_semis DATE NOT NULL');
        $this->addSql('ALTER TABLE historique_irrigation CHANGE date_irrigation date_irrigation DATETIME NOT NULL, CHANGE duree_minutes duree_minutes INT NOT NULL');
        $this->addSql('ALTER TABLE marketplace_order CHANGE seller_id seller_id INT NOT NULL, CHANGE quantity quantity DOUBLE PRECISION NOT NULL, CHANGE unit_price unit_price NUMERIC(10, 2) NOT NULL, CHANGE total_price total_price NUMERIC(10, 2) NOT NULL, CHANGE status status VARCHAR(32) DEFAULT \'pending\' NOT NULL');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE type_mouvement type_mouvement VARCHAR(255) NOT NULL, CHANGE date_mouvement date_mouvement DATETIME NOT NULL, CHANGE quantite quantite INT NOT NULL, CHANGE motif motif VARCHAR(255) NOT NULL, CHANGE id_user id_user INT NOT NULL');
        $this->addSql('ALTER TABLE parcelles CHANGE nom_parcelle nom_parcelle VARCHAR(100) NOT NULL, CHANGE surface surface DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE produit CHANGE nom_produit nom_produit VARCHAR(255) NOT NULL, CHANGE categorie categorie VARCHAR(255) NOT NULL, CHANGE quantite_disponible quantite_disponible INT NOT NULL, CHANGE unite unite VARCHAR(255) NOT NULL, CHANGE seuil_alerte seuil_alerte INT NOT NULL, CHANGE prix_unitaire prix_unitaire INT NOT NULL');
        $this->addSql('ALTER TABLE produit_commentaire CHANGE contenu contenu LONGTEXT NOT NULL, CHANGE date_commentaire date_commentaire DATE NOT NULL');
        $this->addSql('ALTER TABLE recolte CHANGE cout_production cout_production DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE id_parcelle id_parcelle INT NOT NULL, CHANGE nom_systeme nom_systeme VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(255) NOT NULL, CHANGE description_tache description_tache VARCHAR(255) NOT NULL, CHANGE type_tache type_tache VARCHAR(255) NOT NULL, CHANGE id_user id_user INT NOT NULL, CHANGE date_tache date_tache DATE NOT NULL, CHANGE heure_debut_tache heure_debut_tache TIME NOT NULL, CHANGE heure_fin_tache heure_fin_tache TIME NOT NULL, CHANGE status_tache status_tache VARCHAR(255) NOT NULL, CHANGE remarque_tache remarque_tache VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE nom_user nom_user VARCHAR(255) NOT NULL, CHANGE prenom_user prenom_user VARCHAR(255) NOT NULL, CHANGE email_user email_user VARCHAR(255) NOT NULL, CHANGE password password VARCHAR(255) NOT NULL, CHANGE role_user role_user VARCHAR(255) NOT NULL, CHANGE num_user num_user INT NOT NULL, CHANGE adresse_user adresse_user VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE vente CHANGE description description VARCHAR(255) NOT NULL');
    }
}
