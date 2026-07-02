<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702011951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(255) NOT NULL, display_name VARCHAR(255) NOT NULL, google_id VARCHAR(255) DEFAULT NULL, facebook_id VARCHAR(255) DEFAULT NULL, roles CLOB NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON user (email)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__research_request AS SELECT id, client_name, client_email, ancestor_first_name, ancestor_last_name, estimated_birth_date, estimated_birth_place, estimated_death_date, estimated_death_place, research_goals, additional_info, status, created_at FROM research_request');
        $this->addSql('DROP TABLE research_request');
        $this->addSql('CREATE TABLE research_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, client_name VARCHAR(255) NOT NULL, client_email VARCHAR(255) NOT NULL, ancestor_first_name VARCHAR(255) NOT NULL, ancestor_last_name VARCHAR(255) NOT NULL, estimated_birth_date VARCHAR(100) DEFAULT NULL, estimated_birth_place VARCHAR(255) DEFAULT NULL, estimated_death_date VARCHAR(100) DEFAULT NULL, estimated_death_place VARCHAR(255) DEFAULT NULL, research_goals CLOB DEFAULT NULL, additional_info CLOB DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL, client_id INTEGER DEFAULT NULL, CONSTRAINT FK_A2393F8519EB6921 FOREIGN KEY (client_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO research_request (id, client_name, client_email, ancestor_first_name, ancestor_last_name, estimated_birth_date, estimated_birth_place, estimated_death_date, estimated_death_place, research_goals, additional_info, status, created_at) SELECT id, client_name, client_email, ancestor_first_name, ancestor_last_name, estimated_birth_date, estimated_birth_place, estimated_death_date, estimated_death_place, research_goals, additional_info, status, created_at FROM __temp__research_request');
        $this->addSql('DROP TABLE __temp__research_request');
        $this->addSql('CREATE INDEX IDX_A2393F8519EB6921 ON research_request (client_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TEMPORARY TABLE __temp__research_request AS SELECT id, client_name, client_email, ancestor_first_name, ancestor_last_name, estimated_birth_date, estimated_birth_place, estimated_death_date, estimated_death_place, research_goals, additional_info, status, created_at FROM research_request');
        $this->addSql('DROP TABLE research_request');
        $this->addSql('CREATE TABLE research_request (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, client_name VARCHAR(255) NOT NULL, client_email VARCHAR(255) NOT NULL, ancestor_first_name VARCHAR(255) NOT NULL, ancestor_last_name VARCHAR(255) NOT NULL, estimated_birth_date VARCHAR(100) DEFAULT NULL, estimated_birth_place VARCHAR(255) DEFAULT NULL, estimated_death_date VARCHAR(100) DEFAULT NULL, estimated_death_place VARCHAR(255) DEFAULT NULL, research_goals CLOB DEFAULT NULL, additional_info CLOB DEFAULT NULL, status VARCHAR(50) NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO research_request (id, client_name, client_email, ancestor_first_name, ancestor_last_name, estimated_birth_date, estimated_birth_place, estimated_death_date, estimated_death_place, research_goals, additional_info, status, created_at) SELECT id, client_name, client_email, ancestor_first_name, ancestor_last_name, estimated_birth_date, estimated_birth_place, estimated_death_date, estimated_death_place, research_goals, additional_info, status, created_at FROM __temp__research_request');
        $this->addSql('DROP TABLE __temp__research_request');
    }
}
