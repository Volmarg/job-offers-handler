<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230518064541 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE extraction_keyword_2_configuration (id INT AUTO_INCREMENT NOT NULL, extraction_id INT NOT NULL, keyword VARCHAR(255) NOT NULL, configurations LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', expected_configurations LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', INDEX IDX_A6A7333F992488A (extraction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE extraction_keyword_2_configuration ADD CONSTRAINT FK_A6A7333F992488A FOREIGN KEY (extraction_id) REFERENCES job_offer_extraction (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE extraction_keyword_2_configuration');
    }
}
