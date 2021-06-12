<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210612103233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__candidate AS SELECT id, email, first_name, last_name, birth, tag, notes, cv FROM candidate');
        $this->addSql('DROP TABLE candidate');
        $this->addSql('CREATE TABLE candidate (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(255) NOT NULL COLLATE BINARY, first_name VARCHAR(255) NOT NULL COLLATE BINARY, last_name VARCHAR(255) NOT NULL COLLATE BINARY, birth DATE NOT NULL, tag VARCHAR(255) NOT NULL COLLATE BINARY, notes VARCHAR(255) DEFAULT NULL, cv VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO candidate (id, email, first_name, last_name, birth, tag, notes, cv) SELECT id, email, first_name, last_name, birth, tag, notes, cv FROM __temp__candidate');
        $this->addSql('DROP TABLE __temp__candidate');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__candidate AS SELECT id, email, first_name, last_name, birth, tag, notes, cv FROM candidate');
        $this->addSql('DROP TABLE candidate');
        $this->addSql('CREATE TABLE candidate (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, birth DATE NOT NULL, tag VARCHAR(255) NOT NULL, notes VARCHAR(255) NOT NULL COLLATE BINARY, cv VARCHAR(255) NOT NULL COLLATE BINARY)');
        $this->addSql('INSERT INTO candidate (id, email, first_name, last_name, birth, tag, notes, cv) SELECT id, email, first_name, last_name, birth, tag, notes, cv FROM __temp__candidate');
        $this->addSql('DROP TABLE __temp__candidate');
    }
}
