<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260701023528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE research_document (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, file_name VARCHAR(255) NOT NULL, file_path VARCHAR(500) NOT NULL, description CLOB DEFAULT NULL, uploaded_at DATETIME NOT NULL, research_request_id INTEGER NOT NULL, CONSTRAINT FK_2592DDBC8664BECD FOREIGN KEY (research_request_id) REFERENCES research_request (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_2592DDBC8664BECD ON research_document (research_request_id)');
        $this->addSql('CREATE TABLE research_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, client_name VARCHAR(255) NOT NULL, client_email VARCHAR(255) NOT NULL, ancestor_first_name VARCHAR(255) NOT NULL, ancestor_last_name VARCHAR(255) NOT NULL, estimated_birth_date VARCHAR(100) DEFAULT NULL, estimated_birth_place VARCHAR(255) DEFAULT NULL, estimated_death_date VARCHAR(100) DEFAULT NULL, estimated_death_place VARCHAR(255) DEFAULT NULL, research_goals CLOB DEFAULT NULL, additional_info CLOB DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE TABLE messenger_messages (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, body CLOB NOT NULL, headers CLOB NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE research_document');
        $this->addSql('DROP TABLE research_request');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
