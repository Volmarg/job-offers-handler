<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220522054036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE extraction_keyword2_offer (id INT AUTO_INCREMENT NOT NULL, extraction_id INT NOT NULL, job_offer_id INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, keyword VARCHAR(255) NOT NULL, INDEX IDX_B76F11F9F992488A (extraction_id), INDEX IDX_B76F11F93481D195 (job_offer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE extraction_keyword2_offer ADD CONSTRAINT FK_B76F11F9F992488A FOREIGN KEY (extraction_id) REFERENCES job_offer_extraction (id)');
        $this->addSql('ALTER TABLE extraction_keyword2_offer ADD CONSTRAINT FK_B76F11F93481D195 FOREIGN KEY (job_offer_id) REFERENCES job_search_result (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE extraction_keyword2_offer');
    }
}
