<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522105819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE draft_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE profile_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE project_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "user_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE draft (id INT NOT NULL, author_id INT DEFAULT NULL, slug VARCHAR(64) NOT NULL, title VARCHAR(64) DEFAULT NULL, subtitle VARCHAR(64) DEFAULT NULL, description TEXT DEFAULT NULL, images TEXT DEFAULT NULL, products TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_467C9694989D9B62 ON draft (slug)');
        $this->addSql('CREATE INDEX IDX_467C9694F675F31B ON draft (author_id)');
        $this->addSql('COMMENT ON COLUMN draft.images IS \'(DC2Type:simple_array)\'');
        $this->addSql('COMMENT ON COLUMN draft.products IS \'(DC2Type:simple_array)\'');
        $this->addSql('CREATE TABLE profile (id INT NOT NULL, related_user_id INT NOT NULL, full_name VARCHAR(128) DEFAULT NULL, phone VARCHAR(16) DEFAULT NULL, email VARCHAR(128) DEFAULT NULL, city VARCHAR(64) DEFAULT NULL, legal_entity VARCHAR(32) DEFAULT NULL, profession VARCHAR(32) DEFAULT NULL, style VARCHAR(64) DEFAULT NULL, info TEXT DEFAULT NULL, site VARCHAR(64) DEFAULT NULL, social_media VARCHAR(128) DEFAULT NULL, user_agreement_accepted BOOLEAN DEFAULT false NOT NULL, avatar VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8157AA0F98771930 ON profile (related_user_id)');
        $this->addSql('CREATE TABLE project (id INT NOT NULL, author_id INT NOT NULL, slug VARCHAR(64) DEFAULT NULL, title VARCHAR(64) DEFAULT NULL, subtitle VARCHAR(64) DEFAULT NULL, description TEXT DEFAULT NULL, images TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_2FB3D0EEF675F31B ON project (author_id)');
        $this->addSql('COMMENT ON COLUMN project.images IS \'(DC2Type:simple_array)\'');
        $this->addSql('CREATE TABLE "user" (id INT NOT NULL, roles JSON NOT NULL, password VARCHAR(255) DEFAULT NULL, active BOOLEAN NOT NULL, phone VARCHAR(16) DEFAULT NULL, email VARCHAR(128) DEFAULT NULL, name VARCHAR(32) DEFAULT NULL, surname VARCHAR(32) DEFAULT NULL, patronymic VARCHAR(32) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE draft ADD CONSTRAINT FK_467C9694F675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE profile ADD CONSTRAINT FK_8157AA0F98771930 FOREIGN KEY (related_user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEF675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE draft_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE profile_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE project_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "user_id_seq" CASCADE');
        $this->addSql('ALTER TABLE draft DROP CONSTRAINT FK_467C9694F675F31B');
        $this->addSql('ALTER TABLE profile DROP CONSTRAINT FK_8157AA0F98771930');
        $this->addSql('ALTER TABLE project DROP CONSTRAINT FK_2FB3D0EEF675F31B');
        $this->addSql('DROP TABLE draft');
        $this->addSql('DROP TABLE profile');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE "user"');
    }
}
