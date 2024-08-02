<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Exception\IrreversibleMigration;

final class Version20240629123952 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'DB conf: disable ONLY_FULL_GROUP_BY';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql("SET PERSIST sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");

    }

    public function down(Schema $schema) : void
    {
        throw new IrreversibleMigration();
    }
}
