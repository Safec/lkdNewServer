<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * @noinspection PhpUnused
 */
final class Version20230706101814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD status VARCHAR(16) DEFAULT \'NEW\' NOT NULL');
        $this->addSql('UPDATE "user" SET status = \'ACTIVE\' WHERE active = true');
        $this->addSql('ALTER TABLE "user" ADD moderate_by VARCHAR(128) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP status');
        $this->addSql('ALTER TABLE "user" DROP moderate_by');
    }
}
