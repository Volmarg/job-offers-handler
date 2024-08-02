<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221102160109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE job_search_result ADD first_time_found_extraction_id INT NOT NULL');
        $this->addSql('ALTER TABLE job_search_result ADD CONSTRAINT FK_6158F0C0EFCD46B9 FOREIGN KEY (first_time_found_extraction_id) REFERENCES job_offer_extraction (id)');
    }

    public function down(Schema $schema): void
    {
        // no going back
    }
}
