<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190302215320 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_E11EE94DD17F50A6 ON items (uuid)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AA5A118E1D775834 ON tokens (value)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_AA5A118EE16C6B94 ON tokens (alias)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_E11EE94DD17F50A6');
        $this->addSql('DROP INDEX UNIQ_AA5A118E1D775834');
        $this->addSql('DROP INDEX UNIQ_AA5A118EE16C6B94');
    }
}
