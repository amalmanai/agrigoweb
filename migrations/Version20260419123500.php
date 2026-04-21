<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419123500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bad_word_comment_strikes column on user for banned-comment strike tracking.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD COLUMN IF NOT EXISTS bad_word_comment_strikes INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN IF EXISTS bad_word_comment_strikes');
    }
}
