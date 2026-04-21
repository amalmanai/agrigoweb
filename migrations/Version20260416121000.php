<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add face_descriptor column to user table for face recognition data.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD face_descriptor LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP COLUMN face_descriptor');
    }
}
