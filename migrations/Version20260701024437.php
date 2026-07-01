<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260701024437 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE research_document ADD COLUMN category VARCHAR(20) DEFAULT \'research\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__research_document AS SELECT id, file_name, file_path, description, uploaded_at, research_request_id FROM research_document');
        $this->addSql('DROP TABLE research_document');
        $this->addSql('CREATE TABLE research_document (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, file_name VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, description CLOB DEFAULT NULL, uploaded_at DATETIME NOT NULL, research_request_id INTEGER NOT NULL, CONSTRAINT FK_2592DDBC8664BECD FOREIGN KEY (research_request_id) REFERENCES research_request (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO research_document (id, file_name, file_path, description, uploaded_at, research_request_id) SELECT id, file_name, file_path, description, uploaded_at, research_request_id FROM __temp__research_document');
        $this->addSql('DROP TABLE __temp__research_document');
        $this->addSql('CREATE INDEX IDX_2592DDBC8664BECD ON research_document (research_request_id)');
    }
}
