<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220112162714 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_offer_extraction (id INT AUTO_INCREMENT NOT NULL, keywords LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', pagination_pages_count INT NOT NULL, sources LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', created DATETIME NOT NULL, configurations LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', extraction_count INT NOT NULL, new_offers_count INT NOT NULL COMMENT "Only not found EVER before", bound_offers_count INT NOT NULL COMMENT "Duplicated binding are not included", status VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result (id INT AUTO_INCREMENT NOT NULL, extraction_id INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL, job_posted_date_time DATETIME DEFAULT NULL, job_title LONGTEXT NOT NULL, job_description LONGTEXT NOT NULL, job_offer_url LONGTEXT NOT NULL, job_offer_host VARCHAR(255) NOT NULL, company_name VARCHAR(255) DEFAULT NULL, company_country TINYTEXT DEFAULT NULL, contact_email VARCHAR(50) DEFAULT NULL, contact_phone_number VARCHAR(50) DEFAULT NULL, mentioned_human_languages LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', offer_language VARCHAR(50) DEFAULT NULL, salary_min INT NOT NULL, salary_max INT NOT NULL, salary_average INT NOT NULL, remote_job_mentioned TINYINT(1) NOT NULL, keywords LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:array)\', discr VARCHAR(255) NOT NULL, INDEX IDX_6156F0C0F992488A (extraction_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_location (job_search_result_id INT NOT NULL, location_id INT NOT NULL, INDEX IDX_B45FDBDB615A466D (job_search_result_id), INDEX IDX_B45FDBDB64D218E (location_id), PRIMARY KEY(job_search_result_id, location_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_general (id INT NOT NULL, configuration_name VARCHAR(150) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_indeed_de (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_kimeta_de (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_stepstone_de (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_xing_com (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE location (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, name VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, latitude DOUBLE PRECISION DEFAULT NULL, country_code VARCHAR(75) DEFAULT NULL, UNIQUE INDEX UNIQ_5E9E89CB5E237E065373C96685E16F6B4118D123 (name, country, longitude, latitude), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE location_distance (id INT AUTO_INCREMENT NOT NULL, first_location_id INT NOT NULL, second_location_id INT NOT NULL, created DATETIME NOT NULL, distance DOUBLE PRECISION NOT NULL, INDEX IDX_CDC7B86398B82C4C (first_location_id), INDEX IDX_CDC7B8636FA8BD90 (second_location_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job_search_result ADD CONSTRAINT FK_6156F0C0F992488A FOREIGN KEY (extraction_id) REFERENCES job_offer_extraction (id)');
        $this->addSql('ALTER TABLE job_search_result_location ADD CONSTRAINT FK_B45FDBDB615A466D FOREIGN KEY (job_search_result_id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_location ADD CONSTRAINT FK_B45FDBDB64D218E FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_general ADD CONSTRAINT FK_DA3C1FCEBF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_indeed_de ADD CONSTRAINT FK_570A7C61BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_kimeta_de ADD CONSTRAINT FK_8CFF562DBF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_stepstone_de ADD CONSTRAINT FK_BE81BBF3BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_xing_com ADD CONSTRAINT FK_FF0DCB1DBF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location_distance DROP FOREIGN KEY FK_CDC7B86398B82C4C');
        $this->addSql('ALTER TABLE location_distance DROP FOREIGN KEY FK_CDC7B8636FA8BD90');
        $this->addSql('ALTER TABLE job_search_result DROP FOREIGN KEY FK_6156F0C0F992488A');
        $this->addSql('ALTER TABLE job_search_result_location DROP FOREIGN KEY FK_B45FDBDB615A466D');
        $this->addSql('ALTER TABLE job_search_result_general DROP FOREIGN KEY FK_DA3C1FCEBF396750');
        $this->addSql('ALTER TABLE job_search_result_indeed_de DROP FOREIGN KEY FK_570A7C61BF396750');
        $this->addSql('ALTER TABLE job_search_result_kimeta_de DROP FOREIGN KEY FK_8CFF562DBF396750');
        $this->addSql('ALTER TABLE job_search_result_stepstone_de DROP FOREIGN KEY FK_BE81BBF3BF396750');
        $this->addSql('ALTER TABLE job_search_result_xing_com DROP FOREIGN KEY FK_FF0DCB1DBF396750');
        $this->addSql('ALTER TABLE job_search_result_location DROP FOREIGN KEY FK_B45FDBDB64D218E');
        $this->addSql('DROP TABLE job_offer_extraction');
        $this->addSql('DROP TABLE job_search_result');
        $this->addSql('DROP TABLE job_search_result_location');
        $this->addSql('DROP TABLE job_search_result_general');
        $this->addSql('DROP TABLE job_search_result_indeed_de');
        $this->addSql('DROP TABLE job_search_result_kimeta_de');
        $this->addSql('DROP TABLE job_search_result_stepstone_de');
        $this->addSql('DROP TABLE job_search_result_xing_com');
        $this->addSql('DROP TABLE location');
        $this->addSql('DROP TABLE location_distance');
    }
}
