<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417200747 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Add the date_commentaire column to produit_commentaire if it doesn't exist
        $this->addSql('ALTER TABLE produit_commentaire ADD COLUMN date_commentaire DATE NOT NULL DEFAULT CURDATE()');
    }

    public function down(Schema $schema): void
    {
        // Remove the date_commentaire column
        $this->addSql('ALTER TABLE produit_commentaire DROP date_commentaire');
    }
}
