<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230523155620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE requisites_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE requisites (id INT NOT NULL, related_user_id INT NOT NULL, legal_entity VARCHAR(16) DEFAULT NULL, full_name VARCHAR(128) DEFAULT NULL, phone VARCHAR(16) DEFAULT NULL, email VARCHAR(128) DEFAULT NULL, birthdate DATE DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, inn VARCHAR(16) DEFAULT NULL, kpp VARCHAR(16) DEFAULT NULL, ogrn VARCHAR(16) DEFAULT NULL, ogrnip VARCHAR(16) DEFAULT NULL, snils VARCHAR(16) DEFAULT NULL, passport VARCHAR(16) DEFAULT NULL, bank_account_number VARCHAR(32) DEFAULT NULL, bank_correspondent_account VARCHAR(32) DEFAULT NULL, bank VARCHAR(64) DEFAULT NULL, bik VARCHAR(16) DEFAULT NULL, company VARCHAR(64) DEFAULT NULL, charter VARCHAR(32) DEFAULT NULL, legal_address VARCHAR(255) DEFAULT NULL, mailing_address VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8CB1945698771930 ON requisites (related_user_id)');
        $this->addSql('ALTER TABLE requisites ADD CONSTRAINT FK_8CB1945698771930 FOREIGN KEY (related_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE requisites_id_seq CASCADE');
        $this->addSql('ALTER TABLE requisites DROP CONSTRAINT FK_8CB1945698771930');
        $this->addSql('DROP TABLE requisites');
    }
}
