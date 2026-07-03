<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260703224225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conversation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, last_activity_at DATETIME DEFAULT NULL, client_id INTEGER NOT NULL, CONSTRAINT FK_8A8E26E919EB6921 FOREIGN KEY (client_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CONVERSATION_CLIENT ON conversation (client_id)');
        $this->addSql('CREATE TABLE message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, is_from_admin BOOLEAN NOT NULL, content CLOB NOT NULL, author_label VARCHAR(100) DEFAULT NULL, created_at DATETIME NOT NULL, read_at DATETIME DEFAULT NULL, conversation_id INTEGER NOT NULL, CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B6BD307F9AC0396 ON message (conversation_id)');
        // Accélère les compteurs de non-lus (is_from_admin + read_at IS NULL)
        // et l'ordre d'affichage d'un fil (conversation_id + created_at).
        $this->addSql('CREATE INDEX IDX_MESSAGE_UNREAD ON message (is_from_admin, read_at)');
        $this->addSql('CREATE INDEX IDX_MESSAGE_THREAD_ORDER ON message (conversation_id, created_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE message');
    }
}
