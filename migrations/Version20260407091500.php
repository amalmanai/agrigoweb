<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260407091500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix produit table schema: make id_produit auto-increment and align column lengths with Produit entity.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit CHANGE id_produit id_produit INT AUTO_INCREMENT NOT NULL, CHANGE unite unite VARCHAR(255) NOT NULL, CHANGE date_expiration date_expiration VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE produit CHANGE id_produit id_produit INT NOT NULL, CHANGE unite unite VARCHAR(50) NOT NULL, CHANGE date_expiration date_expiration VARCHAR(20) DEFAULT NULL');
    }
}
