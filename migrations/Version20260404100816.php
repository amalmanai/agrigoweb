<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404100816 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE IF NOT EXISTS recolte (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, harvest_date DATE NOT NULL, quantity DOUBLE PRECISION NOT NULL, unit VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS vente (id INT AUTO_INCREMENT NOT NULL, sale_date DATE NOT NULL, price DOUBLE PRECISION NOT NULL, buyer_name VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, recolte_id INT NOT NULL, INDEX IDX_888A2A4CC2C4F051 (recolte_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CC2C4F051 FOREIGN KEY (recolte_id) REFERENCES recolte (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CC2C4F051');
        $this->addSql('DROP TABLE IF EXISTS recolte');
        $this->addSql('DROP TABLE IF EXISTS vente');
    }
}
