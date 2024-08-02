<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220731063251 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE extraction2_amqp_request (id INT AUTO_INCREMENT NOT NULL, extraction_id INT NOT NULL, amqp_request_id INT NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, UNIQUE INDEX UNIQ_2B6E032DF992488A (extraction_id), UNIQUE INDEX UNIQ_2B6E032D1EABBF5A (amqp_request_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE extraction2_amqp_request ADD CONSTRAINT FK_2B6E032DF992488A FOREIGN KEY (extraction_id) REFERENCES job_offer_extraction (id)');
        $this->addSql('ALTER TABLE extraction2_amqp_request ADD CONSTRAINT FK_2B6E032D1EABBF5A FOREIGN KEY (amqp_request_id) REFERENCES amqp_storage (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE extraction2_amqp_request');
        $this->addSql('DROP INDEX country ON location');
    }
}
