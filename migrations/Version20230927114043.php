<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230927114043 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_search_result_bank_job_de (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_aplikuj_pl (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_fach_praca_pl (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_goldenline_pl (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_gowork_pl (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_indeed_pl (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_infopraca_pl (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_interviewme_pl (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_jobs_pl (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_jooble_org (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_praca_pl (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_pracuj_pl (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_pol_theprotocol_it (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job_search_result_bank_job_de ADD CONSTRAINT FK_91E44165BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_aplikuj_pl ADD CONSTRAINT FK_A36525B7BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_fach_praca_pl ADD CONSTRAINT FK_F4026F6EBF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_goldenline_pl ADD CONSTRAINT FK_370F2EF0BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_gowork_pl ADD CONSTRAINT FK_4AC5540ABF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_indeed_pl ADD CONSTRAINT FK_33A371F7BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_infopraca_pl ADD CONSTRAINT FK_703EA4D3BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_interviewme_pl ADD CONSTRAINT FK_F568B534BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_jobs_pl ADD CONSTRAINT FK_BB17F921BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_jooble_org ADD CONSTRAINT FK_9FEBF1CABF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_praca_pl ADD CONSTRAINT FK_1240238ABF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_pracuj_pl ADD CONSTRAINT FK_1C8A8DB2BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_pol_theprotocol_it ADD CONSTRAINT FK_98970D73BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_jobs_de ADD CONSTRAINT FK_9ECC2178BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_jobware ADD CONSTRAINT FK_23C3CB62BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_monster_de ADD CONSTRAINT FK_C8F194B0BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_tideri_de ADD CONSTRAINT FK_20EA4311BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result_bank_job_de DROP FOREIGN KEY FK_91E44165BF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_aplikuj_pl DROP FOREIGN KEY FK_A36525B7BF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_fach_praca_pl DROP FOREIGN KEY FK_F4026F6EBF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_goldenline_pl DROP FOREIGN KEY FK_370F2EF0BF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_gowork_pl DROP FOREIGN KEY FK_4AC5540ABF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_indeed_pl DROP FOREIGN KEY FK_33A371F7BF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_infopraca_pl DROP FOREIGN KEY FK_703EA4D3BF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_interviewme_pl DROP FOREIGN KEY FK_F568B534BF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_jobs_pl DROP FOREIGN KEY FK_BB17F921BF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_jooble_org DROP FOREIGN KEY FK_9FEBF1CABF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_praca_pl DROP FOREIGN KEY FK_1240238ABF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_pracuj_pl DROP FOREIGN KEY FK_1C8A8DB2BF396750');
        $this->addSql('ALTER TABLE job_search_result_pol_theprotocol_it DROP FOREIGN KEY FK_98970D73BF396750');
        $this->addSql('DROP TABLE job_search_result_bank_job_de');
        $this->addSql('DROP TABLE job_search_result_pol_aplikuj_pl');
        $this->addSql('DROP TABLE job_search_result_pol_fach_praca_pl');
        $this->addSql('DROP TABLE job_search_result_pol_goldenline_pl');
        $this->addSql('DROP TABLE job_search_result_pol_gowork_pl');
        $this->addSql('DROP TABLE job_search_result_pol_indeed_pl');
        $this->addSql('DROP TABLE job_search_result_pol_infopraca_pl');
        $this->addSql('DROP TABLE job_search_result_pol_interviewme_pl');
        $this->addSql('DROP TABLE job_search_result_pol_jobs_pl');
        $this->addSql('DROP TABLE job_search_result_pol_jooble_org');
        $this->addSql('DROP TABLE job_search_result_pol_praca_pl');
        $this->addSql('DROP TABLE job_search_result_pol_pracuj_pl');
        $this->addSql('DROP TABLE job_search_result_pol_theprotocol_it');
        $this->addSql('ALTER TABLE job_search_result_jobs_de DROP FOREIGN KEY FK_9ECC2178BF396750');
        $this->addSql('ALTER TABLE job_search_result_jobware DROP FOREIGN KEY FK_23C3CB62BF396750');
        $this->addSql('ALTER TABLE job_search_result_monster_de DROP FOREIGN KEY FK_C8F194B0BF396750');
        $this->addSql('ALTER TABLE job_search_result_tideri_de DROP FOREIGN KEY FK_20EA4311BF396750');
    }
}
