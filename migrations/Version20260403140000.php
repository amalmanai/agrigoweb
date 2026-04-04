<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add reset password fields to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD reset_expires DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN reset_token');
        $this->addSql('ALTER TABLE user DROP COLUMN reset_expires');
    }
}
