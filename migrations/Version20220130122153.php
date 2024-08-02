<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220130122153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, last_time_related_to_offer datetime DEFAULT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE company_branch (id INT AUTO_INCREMENT NOT NULL, location_id INT DEFAULT NULL, emails LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', job_application_emails LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', phone_numbers LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', website LONGTEXT DEFAULT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX IDX_86197F6A64D218E (location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE company_branch ADD CONSTRAINT FK_86197F6A64D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('ALTER TABLE job_search_result ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE job_search_result ADD CONSTRAINT FK_6156F0C0979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_6156F0C0979B1AD6 ON job_search_result (company_id)');
        $this->addSql('CREATE INDEX IDX_6156F0C0979B1XC2 ON company (name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result DROP FOREIGN KEY FK_6156F0C0979B1AD6');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE company_branch');
        $this->addSql('DROP INDEX IDX_6156F0C0979B1AD6 ON job_search_result');
        $this->addSql('ALTER TABLE job_search_result DROP company_id');
    }
}
