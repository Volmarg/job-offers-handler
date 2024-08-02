<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220131175137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE company CHANGE created created DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE job_search_result ADD company_branch_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE job_search_result ADD CONSTRAINT FK_6156F0C0EFCD46B9 FOREIGN KEY (company_branch_id) REFERENCES company_branch (id)');
        $this->addSql('CREATE INDEX IDX_6156F0C0EFCD46B9 ON job_search_result (company_branch_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE company CHANGE created created DATETIME NOT NULL');
        $this->addSql('ALTER TABLE job_search_result DROP FOREIGN KEY FK_6156F0C0EFCD46B9');
        $this->addSql('DROP INDEX IDX_6156F0C0EFCD46B9 ON job_search_result');
        $this->addSql('ALTER TABLE job_search_result DROP company_branch_id');
    }
}
