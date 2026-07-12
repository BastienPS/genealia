<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute à research_request le drapeau deletion_requested (admin demande la
 * suppression, en attente de confirmation client) et previous_status (statut
 * avant archivage, pour restauration).
 *
 * Hand-trimmed : la diff générée reconstituait aussi les tables message /
 * request_todo_task / todo_task (dérive cosmétique FK déjà acceptée) — retiré.
 */
final class Version20260712225202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'research_request: deletion_requested + previous_status (archivage soft des demandes)';
    }

    public function up(Schema $schema): void
    {
        // DEFAULT 0 obligatoire : la table est peuplée, SQLite refuse un ADD
        // COLUMN NOT NULL sans valeur par défaut.
        $this->addSql('ALTER TABLE research_request ADD COLUMN deletion_requested BOOLEAN NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE research_request ADD COLUMN previous_status VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE research_request DROP COLUMN deletion_requested');
        $this->addSql('ALTER TABLE research_request DROP COLUMN previous_status');
    }
}