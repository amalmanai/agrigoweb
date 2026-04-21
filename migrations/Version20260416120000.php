<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add login_token column to user table for legacy login token fallback.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD login_token VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN login_token');
    }
}
