<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405003500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure user table contains is_active and reset password columns required by User entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE user ADD COLUMN IF NOT EXISTS reset_token VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN IF NOT EXISTS reset_expires DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN IF EXISTS reset_expires');
        $this->addSql('ALTER TABLE user DROP COLUMN IF EXISTS reset_token');
        $this->addSql('ALTER TABLE user DROP COLUMN IF EXISTS is_active');
    }
}
