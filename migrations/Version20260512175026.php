<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512175026 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE parcelle DROP FOREIGN KEY `parcelle_ibfk_1`');
        $this->addSql('DROP TABLE parcelle');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('ALTER TABLE alertes_risques DROP FOREIGN KEY `FK_D7CFD258B108249D`');
        $this->addSql('DROP INDEX IDX_D7CFD258B108249D ON alertes_risques');
        $this->addSql('ALTER TABLE alertes_risques CHANGE id_culture culture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE alertes_risques ADD CONSTRAINT FK_D7CFD258B108249D FOREIGN KEY (culture_id) REFERENCES cultures (id_culture)');
        $this->addSql('CREATE INDEX IDX_D7CFD258B108249D ON alertes_risques (culture_id)');
        $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY `FK_2C605D674433ED66`');
        $this->addSql('DROP INDEX IDX_2C605D674433ED66 ON cultures');
        $this->addSql('ALTER TABLE cultures CHANGE id_parcelle parcelle_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cultures ADD CONSTRAINT FK_2C605D674433ED66 FOREIGN KEY (parcelle_id) REFERENCES parcelles (id_parcelle) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2C605D674433ED66 ON cultures (parcelle_id)');
        $this->addSql('ALTER TABLE marketplace_order CHANGE ordered_at ordered_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38A76ED395 FOREIGN KEY (user_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_19E82C38F347EFB ON produit_commentaire (produit_id)');
        $this->addSql('CREATE INDEX IDX_19E82C38A76ED395 ON produit_commentaire (user_id)');
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY `tache_ibfk_1`');
        $this->addSql('DROP INDEX id_user ON tache');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(255) DEFAULT NULL, CHANGE description_tache description_tache VARCHAR(255) DEFAULT NULL, CHANGE type_tache type_tache VARCHAR(255) DEFAULT NULL, CHANGE id_user id_user INT DEFAULT NULL, CHANGE status_tache status_tache VARCHAR(255) DEFAULT NULL, CHANGE remarque_tache remarque_tache VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE vente DROP marketplace_listing, DROP available_quantity, DROP prix, DROP date_vente, DROP id_user');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE parcelle (id_parcelle INT AUTO_INCREMENT NOT NULL, nom_parcelle VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, superficie NUMERIC(10, 2) DEFAULT NULL, localisation VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, type_sol VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, coordonnees_gps TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, id_user INT DEFAULT NULL, INDEX id_user (id_user), PRIMARY KEY (id_parcelle)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE utilisateur (id_user INT AUTO_INCREMENT NOT NULL, nom_user VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, prenom_user VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, email_user VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, role_user VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT \'user\' COLLATE `utf8mb4_general_ci`, num_user VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, adresse_user TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, photo_path VARCHAR(500) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, UNIQUE INDEX email_user (email_user), PRIMARY KEY (id_user)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE parcelle ADD CONSTRAINT `parcelle_ibfk_1` FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE alertes_risques DROP FOREIGN KEY FK_D7CFD258B108249D');
        $this->addSql('DROP INDEX IDX_D7CFD258B108249D ON alertes_risques');
        $this->addSql('ALTER TABLE alertes_risques CHANGE culture_id id_culture INT DEFAULT NULL');
        $this->addSql('ALTER TABLE alertes_risques ADD CONSTRAINT `FK_D7CFD258B108249D` FOREIGN KEY (id_culture) REFERENCES cultures (id_culture)');
        $this->addSql('CREATE INDEX IDX_D7CFD258B108249D ON alertes_risques (id_culture)');
        $this->addSql('ALTER TABLE cultures DROP FOREIGN KEY FK_2C605D674433ED66');
        $this->addSql('DROP INDEX IDX_2C605D674433ED66 ON cultures');
        $this->addSql('ALTER TABLE cultures CHANGE parcelle_id id_parcelle INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cultures ADD CONSTRAINT `FK_2C605D674433ED66` FOREIGN KEY (id_parcelle) REFERENCES parcelles (id_parcelle) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_2C605D674433ED66 ON cultures (id_parcelle)');
        $this->addSql('ALTER TABLE marketplace_order CHANGE ordered_at ordered_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38F347EFB');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38A76ED395');
        $this->addSql('DROP INDEX IDX_19E82C38F347EFB ON produit_commentaire');
        $this->addSql('DROP INDEX IDX_19E82C38A76ED395 ON produit_commentaire');
        $this->addSql('ALTER TABLE tache CHANGE tittre_tache tittre_tache VARCHAR(255) NOT NULL, CHANGE description_tache description_tache TEXT DEFAULT NULL, CHANGE type_tache type_tache VARCHAR(100) DEFAULT NULL, CHANGE id_user id_user INT NOT NULL, CHANGE status_tache status_tache VARCHAR(50) DEFAULT NULL, CHANGE remarque_tache remarque_tache TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT `tache_ibfk_1` FOREIGN KEY (id_user) REFERENCES user (id_user) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('CREATE INDEX id_user ON tache (id_user)');
        $this->addSql('ALTER TABLE vente ADD marketplace_listing TINYINT DEFAULT 1 NOT NULL, ADD available_quantity DOUBLE PRECISION DEFAULT NULL, ADD prix DOUBLE PRECISION DEFAULT \'0\', ADD date_vente DATE DEFAULT NULL, ADD id_user INT DEFAULT 1');
    }
}
