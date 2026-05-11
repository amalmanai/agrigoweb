<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260505133731 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketplace_order CHANGE ordered_at ordered_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id_produit) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38A76ED395 FOREIGN KEY (user_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_19E82C38F347EFB ON produit_commentaire (produit_id)');
        $this->addSql('CREATE INDEX IDX_19E82C38A76ED395 ON produit_commentaire (user_id)');
        $this->addSql('ALTER TABLE recolte CHANGE id_user user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE recolte ADD CONSTRAINT FK_3433713CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id_user)');
        $this->addSql('CREATE INDEX IDX_3433713CA76ED395 ON recolte (user_id)');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE date_creation date_creation DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE vente DROP marketplace_listing, DROP available_quantity');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketplace_order CHANGE ordered_at ordered_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38F347EFB');
        $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38A76ED395');
        $this->addSql('DROP INDEX IDX_19E82C38F347EFB ON produit_commentaire');
        $this->addSql('DROP INDEX IDX_19E82C38A76ED395 ON produit_commentaire');
        $this->addSql('ALTER TABLE recolte DROP FOREIGN KEY FK_3433713CA76ED395');
        $this->addSql('DROP INDEX IDX_3433713CA76ED395 ON recolte');
        $this->addSql('ALTER TABLE recolte CHANGE user_id id_user INT DEFAULT NULL');
        $this->addSql('ALTER TABLE systeme_irrigation CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('ALTER TABLE vente ADD marketplace_listing TINYINT DEFAULT 1 NOT NULL, ADD available_quantity DOUBLE PRECISION DEFAULT NULL');
    }
}
