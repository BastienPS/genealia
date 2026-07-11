<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute le lieu et le pays de mariage à la table `ancestor`
 * (marriage_place VARCHAR(255), marriage_country VARCHAR(2) — code ISO).
 *
 * On se limite à ces deux ALTER TABLE ADD/DROP COLUMN : le diff Doctrine
 * voulait en outre reconstruire `message` (dérive pré-existante de FK
 * `NO ACTION`), ce qui supprimerait les index composites IDX_MESSAGE_UNREAD
 * et IDX_MESSAGE_THREAD_ORDER ajoutés manuellement — on évite cette
 * régression. SQLite ≥ 3.35 gère nativement ALTER TABLE DROP COLUMN.
 */
final class Version20260711143846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add marriage place and country columns to the ancestor table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ancestor ADD COLUMN marriage_place VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ancestor ADD COLUMN marriage_country VARCHAR(2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ancestor DROP COLUMN marriage_place');
        $this->addSql('ALTER TABLE ancestor DROP COLUMN marriage_country');
    }
}