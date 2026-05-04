<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing updated_at column to user table for Vich upload timestamp support.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN updated_at');
    }
}