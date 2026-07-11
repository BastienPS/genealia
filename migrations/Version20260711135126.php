<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Crée la table `ancestor` (ancêtres déclarés par les clients dans l'espace
 * des descendants). On se limite à cette seule table : le diff Doctrine
 * voulait en outre reconstruire `message` (pour expliciter ON UPDATE/DELETE
 * NO ACTION sur sa FK), ce qui aurait supprimé les index composites
 * IDX_MESSAGE_UNREAD et IDX_MESSAGE_THREAD_ORDER ajoutés manuellement lors
 * de la feature messagerie — on évite donc cette régression.
 */
final class Version20260711135126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ancestor table for the client space.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ancestor (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, gender VARCHAR(10) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, birth_date DATE DEFAULT NULL, birth_place VARCHAR(255) DEFAULT NULL, death_date DATE DEFAULT NULL, death_place VARCHAR(255) DEFAULT NULL, marriage_date DATE DEFAULT NULL, created_at DATETIME NOT NULL, client_id INTEGER DEFAULT NULL, CONSTRAINT FK_B4465BB19EB6921 FOREIGN KEY (client_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B4465BB19EB6921 ON ancestor (client_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ancestor');
    }
}