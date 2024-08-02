<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220526151550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email (id INT AUTO_INCREMENT NOT NULL, address VARCHAR(255) NOT NULL COMMENT \'The E-Mail address\', is_valid_smtp_check TINYINT(1) NOT NULL, is_accepting_emails TINYINT(1) NOT NULL COMMENT \'Does this E-Mail address accepts incoming messages at all\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE email_2_company (id INT AUTO_INCREMENT NOT NULL, email_id INT DEFAULT NULL, company_id INT NOT NULL, is_for_job_application TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_B710548AA832C1C9 (email_id), INDEX IDX_B710548A979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE email_source (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE email_2_company ADD CONSTRAINT FK_B710548AA832C1C9 FOREIGN KEY (email_id) REFERENCES email (id)');
        $this->addSql('ALTER TABLE email_2_company ADD CONSTRAINT FK_B710548A979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_2_company DROP FOREIGN KEY FK_B710548AA832C1C9');
        $this->addSql('DROP TABLE email');
        $this->addSql('DROP TABLE email_2_company');
        $this->addSql('DROP TABLE email_source');
    }
}
