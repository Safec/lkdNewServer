<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230613082757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profile ADD sites TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE profile ADD social_medias TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE profile DROP site');
        $this->addSql('ALTER TABLE profile DROP social_media');
        $this->addSql('COMMENT ON COLUMN profile.sites IS \'(DC2Type:simple_array)\'');
        $this->addSql('COMMENT ON COLUMN profile.social_medias IS \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE profile ADD site VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE profile ADD social_media VARCHAR(128) DEFAULT NULL');
        $this->addSql('ALTER TABLE profile DROP sites');
        $this->addSql('ALTER TABLE profile DROP social_medias');
    }
}
