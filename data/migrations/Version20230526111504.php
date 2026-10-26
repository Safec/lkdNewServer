<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230526111504 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profile ADD position VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" DROP surname');
        $this->addSql('ALTER TABLE "user" DROP patronymic');
        $this->addSql('ALTER TABLE "user" ALTER name TYPE VARCHAR(64)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "user" ADD surname VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD patronymic VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ALTER name TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE profile DROP position');
    }
}
