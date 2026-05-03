<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260417130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add marketplace fields on vente and create marketplace_order table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vente ADD marketplace_listing TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE vente ADD available_quantity DOUBLE PRECISION DEFAULT NULL');

        $this->addSql('CREATE TABLE marketplace_order (id_order INT AUTO_INCREMENT NOT NULL, vente_id INT NOT NULL, buyer_id INT NOT NULL, seller_id INT NOT NULL, quantity DOUBLE PRECISION NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, total_price NUMERIC(10, 2) NOT NULL, status VARCHAR(32) DEFAULT \'' . 'pending' . '\' NOT NULL, delivery_address VARCHAR(500) DEFAULT NULL, note LONGTEXT DEFAULT NULL, ordered_at DATETIME NOT NULL COMMENT \'' . '(DC2Type:datetime_immutable)' . '\', INDEX IDX_378C3E6A7E0EAFA3 (vente_id), INDEX IDX_378C3E6A8A98A7A5 (buyer_id), PRIMARY KEY(id_order)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE marketplace_order ADD CONSTRAINT FK_378C3E6A7E0EAFA3 FOREIGN KEY (vente_id) REFERENCES vente (id_vente) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_order ADD CONSTRAINT FK_378C3E6A8A98A7A5 FOREIGN KEY (buyer_id) REFERENCES user (id_user) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE marketplace_order DROP FOREIGN KEY FK_378C3E6A7E0EAFA3');
        $this->addSql('ALTER TABLE marketplace_order DROP FOREIGN KEY FK_378C3E6A8A98A7A5');
        $this->addSql('DROP TABLE marketplace_order');

        $this->addSql('ALTER TABLE vente DROP marketplace_listing');
        $this->addSql('ALTER TABLE vente DROP available_quantity');
    }
}
