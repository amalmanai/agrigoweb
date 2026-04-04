<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial clean migration - creates all tables properly
 */
final class Version20260403121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial clean schema creation';
    }

    public function up(Schema $schema): void
    {
        // Create messenger_messages table
        $this->addSql('CREATE TABLE IF NOT EXISTS messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        
        // Create user table
        $this->addSql('CREATE TABLE IF NOT EXISTS user (id_user INT AUTO_INCREMENT NOT NULL, nom_user VARCHAR(255) NOT NULL, prenom_user VARCHAR(255) NOT NULL, email_user VARCHAR(255) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, role_user VARCHAR(255) NOT NULL DEFAULT "ROLE_USER", num_user INT NOT NULL, adresse_user VARCHAR(255) NOT NULL, photo_path VARCHAR(255) DEFAULT NULL, is_active TINYINT DEFAULT 1 NOT NULL, PRIMARY KEY (id_user)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        
        // Drop utilisateur table if it exists (consolidate on user)
        $this->addSql('DROP TABLE IF EXISTS utilisateur');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS messenger_messages');
        $this->addSql('DROP TABLE IF EXISTS user');
    }
}
