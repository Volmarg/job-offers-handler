<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230124185901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE service_state (
                `id` INT NOT NULL AUTO_INCREMENT,
                `created` DATETIME NOT NULL,
                `modified` DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP,
                `name` VARCHAR(250) NOT NULL,
                `active` TINYINT NOT NULL,
                PRIMARY KEY(id)
            )
        ");

        $this->addSql("
            ALTER TABLE service_state ADD UNIQUE INDEX UNIQ_NAME12341x1a3 (name)
        ");

    }

    public function down(Schema $schema): void
    {
        // no going back
    }
}
