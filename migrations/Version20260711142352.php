<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute les champs pays (code ISO 2 lettres) aux ancêtres et aux demandes de
 * recherche : birth_country / death_country sur `ancestor`,
 * estimated_birth_country / estimated_death_country sur `research_request`.
 *
 * On se limite à ces quatre ALTER TABLE ADD/DROP COLUMN : le diff Doctrine
 * voulait en outre reconstruire `message` (pour expliciter ON UPDATE/DELETE
 * NO ACTION sur sa FK), ce qui aurait supprimé les index composites
 * IDX_MESSAGE_UNREAD et IDX_MESSAGE_THREAD_ORDER ajoutés manuellement lors de
 * la feature messagerie — on évite donc cette régression. SQLite ≥ 3.35 gère
 * nativement ALTER TABLE DROP COLUMN (version locale 3.53).
 */
final class Version20260711142352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add country columns to ancestor and research_request tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ancestor ADD COLUMN birth_country VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE ancestor ADD COLUMN death_country VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE research_request ADD COLUMN estimated_birth_country VARCHAR(2) DEFAULT NULL');
        $this->addSql('ALTER TABLE research_request ADD COLUMN estimated_death_country VARCHAR(2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ancestor DROP COLUMN birth_country');
        $this->addSql('ALTER TABLE ancestor DROP COLUMN death_country');
        $this->addSql('ALTER TABLE research_request DROP COLUMN estimated_birth_country');
        $this->addSql('ALTER TABLE research_request DROP COLUMN estimated_death_country');
    }
}