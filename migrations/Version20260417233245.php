<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417233245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE marketplace_order (id_order INT AUTO_INCREMENT NOT NULL, seller_id INT NOT NULL, quantity DOUBLE PRECISION NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, total_price NUMERIC(10, 2) NOT NULL, status VARCHAR(32) DEFAULT \'pending\' NOT NULL, delivery_address VARCHAR(500) DEFAULT NULL, note LONGTEXT DEFAULT NULL, ordered_at DATETIME NOT NULL, vente_id INT NOT NULL, buyer_id INT NOT NULL, INDEX IDX_51EA2CDF7DC7170A (vente_id), INDEX IDX_51EA2CDF6C755722 (buyer_id), PRIMARY KEY (id_order)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE marketplace_order ADD CONSTRAINT FK_51EA2CDF7DC7170A FOREIGN KEY (vente_id) REFERENCES vente (id_vente) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marketplace_order ADD CONSTRAINT FK_51EA2CDF6C755722 FOREIGN KEY (buyer_id) REFERENCES user (id_user) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recolte ADD CONSTRAINT FK_3433713C4433ED66 FOREIGN KEY (parcelle_id) REFERENCES parcelles (id_parcelle) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3433713C4433ED66 ON recolte (parcelle_id)');
        $this->addSql('DROP INDEX FK_888A2A4C6C755722 ON vente');
        $this->addSql('ALTER TABLE vente ADD available_quantity DOUBLE PRECISION DEFAULT NULL, DROP id_user, DROP price, DROP saleDate, DROP sale_date, DROP rating_comment, DROP buyer_id, CHANGE rating rating INT DEFAULT NULL, CHANGE marketplace_listing marketplace_listing TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CC2C4F051 FOREIGN KEY (recolte_id) REFERENCES recolte (id_recolte) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_888A2A4CC2C4F051 ON vente (recolte_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE marketplace_order DROP FOREIGN KEY FK_51EA2CDF7DC7170A');
        $this->addSql('ALTER TABLE marketplace_order DROP FOREIGN KEY FK_51EA2CDF6C755722');
        $this->addSql('DROP TABLE marketplace_order');
        $this->addSql('ALTER TABLE recolte DROP FOREIGN KEY FK_3433713C4433ED66');
        $this->addSql('DROP INDEX IDX_3433713C4433ED66 ON recolte');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CC2C4F051');
        $this->addSql('DROP INDEX IDX_888A2A4CC2C4F051 ON vente');
        $this->addSql('ALTER TABLE vente ADD id_user INT NOT NULL, ADD price NUMERIC(10, 2) DEFAULT NULL, ADD saleDate DATE DEFAULT NULL, ADD sale_date DATETIME DEFAULT NULL, ADD rating_comment LONGTEXT DEFAULT NULL, ADD buyer_id INT DEFAULT NULL, DROP available_quantity, CHANGE rating rating SMALLINT DEFAULT NULL, CHANGE marketplace_listing marketplace_listing TINYINT DEFAULT 0');
        $this->addSql('CREATE INDEX FK_888A2A4C6C755722 ON vente (buyer_id)');
    }
}
