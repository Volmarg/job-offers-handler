<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230117144205 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Extend job_offer_extraction with `modified` column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            ALTER TABLE job_offer_extraction ADD COLUMN modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created`
        ");

    }

    public function down(Schema $schema): void
    {
        // no going back
    }
}
