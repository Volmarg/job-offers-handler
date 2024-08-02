<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220905145708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_offer_extraction_job_search_result (job_search_result_id INT NOT NULL, job_offer_extraction_id INT NOT NULL, INDEX IDX_1CC8DA9E615A466D (job_search_result_id), INDEX IDX_1CC8DA9E5769C8CF (job_offer_extraction_id), PRIMARY KEY(job_search_result_id, job_offer_extraction_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job_offer_extraction_job_search_result ADD CONSTRAINT FK_1CC8DA9E615A466D FOREIGN KEY (job_search_result_id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offer_extraction_job_search_result ADD CONSTRAINT FK_1CC8DA9E5769C8CF FOREIGN KEY (job_offer_extraction_id) REFERENCES job_offer_extraction (id) ON DELETE CASCADE');
        $this->addSql("ALTER TABLE `job_search_result` DROP FOREIGN KEY `FK_6156F0C0F992488A`");
        $this->addSql("ALTER TABLE job_search_result DROP INDEX IDX_6156F0C0F992488A");
        $this->addSql("ALTER TABLE job_search_result DROP extraction_id");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE job_search_result_2_extraction');
    }
}
