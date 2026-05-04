<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure produit_commentaire primary key uses AUTO_INCREMENT to prevent NoIdentityValue on insert.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE IF NOT EXISTS produit_commentaire (
    id_commentaire INT AUTO_INCREMENT NOT NULL,
    contenu LONGTEXT NOT NULL,
    date_commentaire DATE NOT NULL,
    id_produit INT NOT NULL,
    id_user INT NOT NULL,
    INDEX IDX_19E82C38F7384557 (id_produit),
    INDEX IDX_19E82C386B3CA4B (id_user),
    PRIMARY KEY(id_commentaire)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        // Force identity behavior even if table was previously created without AUTO_INCREMENT.
        $this->addSql('ALTER TABLE produit_commentaire MODIFY id_commentaire INT NOT NULL AUTO_INCREMENT');

        // Keep FK constraints aligned with current entity mapping.
        if (!$this->foreignKeyExists('produit_commentaire', 'FK_19E82C38F7384557')) {
            $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C38F7384557 FOREIGN KEY (id_produit) REFERENCES produit (id_produit) ON DELETE CASCADE');
        }

        if (!$this->foreignKeyExists('produit_commentaire', 'FK_19E82C386B3CA4B')) {
            $this->addSql('ALTER TABLE produit_commentaire ADD CONSTRAINT FK_19E82C386B3CA4B FOREIGN KEY (id_user) REFERENCES user (id_user) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        // Revert only identity behavior and constraints; keep data.
        if ($this->foreignKeyExists('produit_commentaire', 'FK_19E82C38F7384557')) {
            $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C38F7384557');
        }

        if ($this->foreignKeyExists('produit_commentaire', 'FK_19E82C386B3CA4B')) {
            $this->addSql('ALTER TABLE produit_commentaire DROP FOREIGN KEY FK_19E82C386B3CA4B');
        }

        $this->addSql('ALTER TABLE produit_commentaire MODIFY id_commentaire INT NOT NULL');
    }

    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        $sql = <<<'SQL'
SELECT COUNT(*)
FROM information_schema.TABLE_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
  AND TABLE_NAME = :table
  AND CONSTRAINT_NAME = :constraint_name
  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
SQL;

        return (int) $this->connection->fetchOne($sql, [
            'table' => $table,
            'constraint_name' => $constraintName,
        ]) > 0;
    }
}
