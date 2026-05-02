<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418184804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add marketplace orders, product comments, and new product columns without altering existing schema.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('marketplace_order')) {
            $this->addSql(<<<'SQL'
CREATE TABLE marketplace_order (
    id_order INT AUTO_INCREMENT NOT NULL,
    vente_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_id INT NOT NULL,
    quantity DOUBLE PRECISION NOT NULL,
    unit_price NUMERIC(10, 2) NOT NULL,
    total_price NUMERIC(10, 2) NOT NULL,
    status VARCHAR(32) NOT NULL,
    delivery_address VARCHAR(500) DEFAULT NULL,
    note LONGTEXT DEFAULT NULL,
    ordered_at DATETIME NOT NULL,
    INDEX IDX_51EA2CDF7DC7170A (vente_id),
    INDEX IDX_51EA2CDF6C755722 (buyer_id),
    INDEX IDX_51EA2CDF5FF201F7 (seller_id),
    PRIMARY KEY(id_order)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        if (!$this->foreignKeyExists('marketplace_order', 'FK_378C3E6A7E0EAFA3')) {
            $this->addSql('ALTER TABLE marketplace_order ADD CONSTRAINT FK_378C3E6A7E0EAFA3 FOREIGN KEY (vente_id) REFERENCES vente (id_vente) ON DELETE CASCADE');
        }

        if (!$this->foreignKeyExists('marketplace_order', 'FK_378C3E6A8A98A7A5')) {
            $this->addSql('ALTER TABLE marketplace_order ADD CONSTRAINT FK_378C3E6A8A98A7A5 FOREIGN KEY (buyer_id) REFERENCES user (id_user) ON DELETE CASCADE');
        }

        if (!$this->tableExists('produit_commentaire')) {
            $this->addSql(<<<'SQL'
CREATE TABLE produit_commentaire (
    id_commentaire INT AUTO_INCREMENT NOT NULL,
    contenu LONGTEXT NOT NULL,
    date_commentaire DATETIME NOT NULL,
    id_produit INT NOT NULL,
    id_user INT NOT NULL,
    INDEX IDX_19E82C38F7384557 (id_produit),
    INDEX IDX_19E82C386B3CA4B (id_user),
    PRIMARY KEY(id_commentaire)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        if (!$this->foreignKeyExists('produit_commentaire', 'FK_19E82C38F7384557')) {
            $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38F7384557 FOREIGN KEY (id_produit) REFERENCES produit (id_produit) ON DELETE CASCADE');
        }

        if (!$this->foreignKeyExists('produit_commentaire', 'FK_19E82C386B3CA4B')) {
            $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C386B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user) ON DELETE CASCADE');
        }

        if (!$this->columnExists('produit', 'commentaire')) {
            $this->addSql('ALTER TABLE produit ADD commentaire LONGTEXT DEFAULT NULL');
        }

        if (!$this->columnExists('produit', 'owner_id')) {
            $this->addSql('ALTER TABLE produit ADD owner_id INT DEFAULT NULL');
        }

        if (!$this->indexExists('produit', 'IDX_29A5EC277E3C61F9')) {
            $this->addSql('CREATE INDEX IDX_29A5EC277E3C61F9 ON produit (owner_id)');
        }

        if (!$this->foreignKeyExists('produit', 'FK_29A5EC277E3C61F9')) {
            $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC277E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id_user) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->foreignKeyExists('produit_commentaire', 'FK_19E82C386B3CA4B')) {
            $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C386B3CA4B');
        }

        if ($this->foreignKeyExists('produit_commentaire', 'FK_19E82C38F7384557')) {
            $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38F7384557');
        }

        if ($this->tableExists('produit_commentaire')) {
            $this->addSql('DROP TABLE produit_commentaire');
        }

        if ($this->foreignKeyExists('marketplace_order', 'FK_378C3E6A8A98A7A5')) {
            $this->addSql('ALTER TABLE marketplace_order DROP FOREIGN KEY FK_378C3E6A8A98A7A5');
        }

        if ($this->foreignKeyExists('marketplace_order', 'FK_378C3E6A7E0EAFA3')) {
            $this->addSql('ALTER TABLE marketplace_order DROP FOREIGN KEY FK_378C3E6A7E0EAFA3');
        }

        if ($this->tableExists('marketplace_order')) {
            $this->addSql('DROP TABLE marketplace_order');
        }

        if ($this->foreignKeyExists('produit', 'FK_29A5EC277E3C61F9')) {
            $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC277E3C61F9');
        }

        if ($this->indexExists('produit', 'IDX_29A5EC277E3C61F9')) {
            $this->addSql('DROP INDEX IDX_29A5EC277E3C61F9 ON produit');
        }

        if ($this->columnExists('produit', 'owner_id')) {
            $this->addSql('ALTER TABLE produit DROP owner_id');
        }

        if ($this->columnExists('produit', 'commentaire')) {
            $this->addSql('ALTER TABLE produit DROP commentaire');
        }
    }

    private function tableExists(string $tableName): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?',
            [$tableName]
        );
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$tableName, $columnName]
        );
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        if (!$this->tableExists($tableName)) {
            return false;
        }

        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            [$tableName, $indexName]
        );
    }

    private function foreignKeyExists(string $tableName, string $foreignKeyName): bool
    {
        if (!$this->tableExists($tableName)) {
            return false;
        }

        return (bool) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.table_constraints WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ? AND constraint_type = "FOREIGN KEY"',
            [$tableName, $foreignKeyName]
        );
    }
}
