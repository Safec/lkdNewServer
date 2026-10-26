<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230714112835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Меняет формат КПП с `12 34 56 789` на `123456789`.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE requisites SET kpp = replace(kpp, ' ', '')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE requisites SET kpp = substr(kpp, 1, 2) || ' ' || substr(kpp, 3, 2) || ' ' || substr(kpp, 5, 2) || ' ' || substr(kpp, 7)");
    }
}
