<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Compteur de tentatives de commentaires avec mots interdits (bad words) par utilisateur.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD bad_word_comment_strikes INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP bad_word_comment_strikes');
    }
}
