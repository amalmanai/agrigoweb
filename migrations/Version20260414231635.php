<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414231635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajouter le champ commentaire à la table produit';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit ADD commentaire LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit DROP commentaire');
    }
}
