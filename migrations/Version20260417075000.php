<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Add delivery location fields to vente table
 */
final class Version20260417075000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add delivery location fields (address, latitude, longitude) to vente table for mapping';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vente ADD delivery_location VARCHAR(500) DEFAULT NULL');
        $this->addSql('ALTER TABLE vente ADD delivery_latitude VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE vente ADD delivery_longitude VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE vente DROP COLUMN delivery_location');
        $this->addSql('ALTER TABLE vente DROP COLUMN delivery_latitude');
        $this->addSql('ALTER TABLE vente DROP COLUMN delivery_longitude');
    }
}
