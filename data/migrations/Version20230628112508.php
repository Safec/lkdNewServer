<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230628112508 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE profile ADD styles TEXT DEFAULT NULL');
        $this->addSql('UPDATE profile SET styles = style WHERE style != \'\' AND style IS NOT NULL');
        $this->addSql('ALTER TABLE profile DROP style');
        $this->addSql('COMMENT ON COLUMN profile.styles IS \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE profile ADD style VARCHAR(64) DEFAULT NULL');
        $this->addSql('UPDATE profile SET style = (string_to_array(styles, \',\'))[1] WHERE styles != \'\' AND styles IS NOT NULL');
        $this->addSql('ALTER TABLE profile DROP styles');
    }
}
