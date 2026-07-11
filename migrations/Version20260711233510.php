<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260711233510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Todolists administratives (modèles réutilisables) + leurs tâches ordonnées.
        $this->addSql('CREATE TABLE todo_list (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE todo_task (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, label VARCHAR(500) NOT NULL, position INTEGER NOT NULL, done BOOLEAN NOT NULL, created_at DATETIME NOT NULL, todo_list_id INTEGER NOT NULL, CONSTRAINT FK_DAFBD3AE8A7DCFA FOREIGN KEY (todo_list_id) REFERENCES todo_list (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_DAFBD3AE8A7DCFA ON todo_task (todo_list_id)');
        // Accélère le rendu ordonné des tâches d'une liste.
        $this->addSql('CREATE INDEX IDX_TODO_TASK_ORDER ON todo_task (todo_list_id, position)');

        // NB : doctrine:migrations:diff voulait aussi reconstruire la table
        // `message` (dérive préexistante de FK ON UPDATE/DELETE NO ACTION) ;
        // on l'ignore ici pour ne pas perdre les index composites ajoutés
        // manuellement (IDX_MESSAGE_UNREAD / IDX_MESSAGE_THREAD_ORDER).
    }

    public function down(Schema $schema): void
    {
        // Enfant d'abord (FK vers todo_list).
        $this->addSql('DROP TABLE todo_task');
        $this->addSql('DROP TABLE todo_list');
    }
}
