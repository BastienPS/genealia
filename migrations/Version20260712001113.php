<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Snapshots de todolists appliquées aux ResearchRequest (phase 2) :
 * request_todo_list (1:1 unique par demande) + request_todo_task.
 *
 * Hand-trimmed : la dérive cosmétique de rebuild de `message` et `todo_task`
 * (FK ON UPDATE/DELETE NO ACTION) est retirée — c'est un artefact du comparateur
 * Doctrine/SQLite, préexistant et accepté (cf. Version20260711233510).
 */
final class Version20260712001113 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crée les tables snapshot request_todo_list + request_todo_task (todolist appliquée à une demande).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE request_todo_list (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, request_id INTEGER NOT NULL, CONSTRAINT FK_50E86FA0427EB8A5 FOREIGN KEY (request_id) REFERENCES research_request (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_50E86FA0427EB8A5 ON request_todo_list (request_id)');
        $this->addSql('CREATE TABLE request_todo_task (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, label VARCHAR(500) NOT NULL, position INTEGER NOT NULL, done BOOLEAN NOT NULL, created_at DATETIME NOT NULL, request_todo_list_id INTEGER NOT NULL, CONSTRAINT FK_465E4C9DF38D8522 FOREIGN KEY (request_todo_list_id) REFERENCES request_todo_list (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_465E4C9DF38D8522 ON request_todo_task (request_todo_list_id)');
        $this->addSql('CREATE INDEX IDX_REQUEST_TODO_TASK_ORDER ON request_todo_task (request_todo_list_id, position)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE request_todo_task');
        $this->addSql('DROP TABLE request_todo_list');
    }
}