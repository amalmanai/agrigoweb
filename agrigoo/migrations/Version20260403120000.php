<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to remove utilisateur table and consolidate on user table
 */
final class Version20260403120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove utilisateur table and consolidate user management on user table';
    }

    public function up(Schema $schema): void
    {
        // Disable foreign key checks
        $this->addSql('SET FOREIGN_KEY_CHECKS = 0');
        
        // Drop the utilisateur table if it exists
        $this->addSql('DROP TABLE IF EXISTS utilisateur');
        
        // Re-enable foreign key checks
        $this->addSql('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(Schema $schema): void
    {
        // Recreate utilisateur table if needed
        $this->addSql('CREATE TABLE utilisateur (id_user INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, adresse VARCHAR(255) DEFAULT NULL, telephone VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id_user)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Restore the foreign key
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4C6834359B');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4C6B3CA4B FOREIGN KEY (id_user) REFERENCES utilisateur (id_user)');
    }
}
