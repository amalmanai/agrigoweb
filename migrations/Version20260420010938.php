<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260420010938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mouvement_stock CHANGE date_mouvement date_mouvement DATE NOT NULL');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_61E2C8EBF7384557 FOREIGN KEY (id_produit) REFERENCES produit (id_produit)');
        $this->addSql('CREATE INDEX IDX_61E2C8EBF7384557 ON mouvement_stock (id_produit)');
        $this->addSql('ALTER TABLE produit ADD commentaire LONGTEXT DEFAULT NULL, ADD owner_id INT DEFAULT NULL, CHANGE date_expiration date_expiration DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC277E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_29A5EC277E3C61F9 ON produit (owner_id)');
        $this->addSql('ALTER TABLE produit_commentaire CHANGE id_commentaire id_commentaire INT AUTO_INCREMENT NOT NULL, ADD PRIMARY KEY (id_commentaire)');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38F7384557 FOREIGN KEY (id_produit) REFERENCES produit (id_produit) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C386B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_19E82C38F7384557 ON produit_commentaire (id_produit)');
        $this->addSql('CREATE INDEX IDX_19E82C386B3CA4B ON produit_commentaire (id_user)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EBF7384557');
        $this->addSql('DROP INDEX IDX_61E2C8EBF7384557 ON mouvement_stock');
        $this->addSql('ALTER TABLE mouvement_stock CHANGE date_mouvement date_mouvement VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC277E3C61F9');
        $this->addSql('DROP INDEX IDX_29A5EC277E3C61F9 ON produit');
        $this->addSql('ALTER TABLE produit DROP commentaire, DROP owner_id, CHANGE date_expiration date_expiration VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38F7384557');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C386B3CA4B');
        $this->addSql('DROP INDEX IDX_19E82C38F7384557 ON produit_commentaire');
        $this->addSql('DROP INDEX IDX_19E82C386B3CA4B ON produit_commentaire');
        $this->addSql('ALTER TABLE produit_commentaire MODIFY id_commentaire INT NOT NULL');
        $this->addSql('ALTER TABLE produit_commentaire CHANGE id_commentaire id_commentaire INT NOT NULL, DROP PRIMARY KEY');
    }
}
