<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix recolte and vente table schemas to match Entity mappings.';
    }

    public function up(Schema $schema): void
    {
        // Drop foreign key constraint first
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CC2C4F051');
        
        // Drop existing tables
        $this->addSql('DROP TABLE IF EXISTS vente');
        $this->addSql('DROP TABLE IF EXISTS recolte');
        
        // Create recolte table with correct column names
        $this->addSql('CREATE TABLE recolte (
            id_recolte INT AUTO_INCREMENT NOT NULL,
            nom_produit VARCHAR(100) DEFAULT NULL,
            quantite DOUBLE PRECISION DEFAULT NULL,
            unite VARCHAR(20) DEFAULT NULL,
            date_recolte DATE DEFAULT NULL,
            cout_production DOUBLE PRECISION DEFAULT NULL,
            id_user INT DEFAULT NULL,
            PRIMARY KEY (id_recolte)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        
        // Create vente table with correct column names and FK
        $this->addSql('CREATE TABLE vente (
            id_vente INT AUTO_INCREMENT NOT NULL,
            recolte_id INT DEFAULT NULL,
            description VARCHAR(255) DEFAULT NULL,
            price DECIMAL(10, 2) DEFAULT NULL,
            sale_date DATE DEFAULT NULL,
            buyer_name VARCHAR(255) DEFAULT NULL,
            status VARCHAR(255) DEFAULT NULL,
            INDEX IDX_VENTE_RECOLTE (recolte_id),
            PRIMARY KEY (id_vente)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        
        // Add foreign key constraint
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_VENTE_RECOLTE FOREIGN KEY (recolte_id) REFERENCES recolte (id_recolte) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraint
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_VENTE_RECOLTE');
        
        // Drop tables
        $this->addSql('DROP TABLE IF EXISTS vente');
        $this->addSql('DROP TABLE IF EXISTS recolte');
    }
}
