<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231020125822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_search_result_elempleo_com_esp (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_talent_esp (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_info_elempleo_com_esp (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_info_jobs_net_esp (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_es_jooble_org_esp (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_es_indeed_esp (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_jooble_se_org_swe (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_jobb_safari_se_swe (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_jobb_guru_se_swe (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_jobb_blocket_se_swe (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_jobland_se_swe (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_monster_se_swe (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_jobs_in_stockholm_com_swe (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_no_indeed_nor (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_jobb_safari_no_nor (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_jobs_in_norway_com_nor (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_jooble_org_no_nor (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE job_search_result_elempleo_com_esp ADD CONSTRAINT FK_74458760BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_talent_esp ADD CONSTRAINT FK_7194A39EBF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_info_elempleo_com_esp ADD CONSTRAINT FK_7194A39EBF396751 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_info_jobs_net_esp ADD CONSTRAINT FK_7194A39EBF396752 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_es_jooble_org_esp ADD CONSTRAINT FK_7194A39EBF396753 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_es_indeed_esp ADD CONSTRAINT FK_7194A39EBF396754 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_jooble_se_org_swe ADD CONSTRAINT FK_7194A39EBF396755 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_jobb_safari_se_swe ADD CONSTRAINT FK_7194A39EBF396756 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_jobb_guru_se_swe ADD CONSTRAINT FK_7194A39EBF396757 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_jobb_blocket_se_swe ADD CONSTRAINT FK_7194A39EBF396758 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_jobland_se_swe ADD CONSTRAINT FK_7194A39EBF396759 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_monster_se_swe ADD CONSTRAINT FK_7194A39EBF396760 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_jobs_in_stockholm_com_swe ADD CONSTRAINT FK_7194A39EBF396761 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_no_indeed_nor ADD CONSTRAINT FK_7194A39EBF396766 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_jobb_safari_no_nor ADD CONSTRAINT FK_7194A39EBF396767 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_jobs_in_norway_com_nor ADD CONSTRAINT FK_7194A39EBF396768 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_jooble_org_no_nor ADD CONSTRAINT FK_7194A39EBF396769 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result_info_elempleo_com_esp DROP FOREIGN KEY FK_7194A39EBF396751');
        $this->addSql('ALTER TABLE job_search_result_info_jobs_net_esp DROP FOREIGN KEY FK_7194A39EBF396752');
        $this->addSql('ALTER TABLE job_search_result_elempleo_com_esp DROP FOREIGN KEY FK_74458760BF396750');
        $this->addSql('ALTER TABLE job_search_result_talent_esp DROP FOREIGN KEY FK_7194A39EBF396750');
        $this->addSql('ALTER TABLE job_search_result_es_jooble_org_esp DROP FOREIGN KEY FK_7194A39EBF396753');
        $this->addSql('ALTER TABLE job_search_result_es_indeed_esp DROP FOREIGN KEY FK_7194A39EBF396754');
        $this->addSql('ALTER TABLE job_search_result_jooble_se_org_swe DROP FOREIGN KEY FK_7194A39EBF396755');
        $this->addSql('ALTER TABLE job_search_result_jobb_safari_se_swe DROP FOREIGN KEY FK_7194A39EBF396756');
        $this->addSql('ALTER TABLE job_search_result_jobb_guru_se_swe DROP FOREIGN KEY FK_7194A39EBF396757');
        $this->addSql('ALTER TABLE job_search_result_jobb_blocket_se_swe DROP FOREIGN KEY FK_7194A39EBF396758');
        $this->addSql('ALTER TABLE job_search_result_jobland_se_swe DROP FOREIGN KEY FK_7194A39EBF396759');
        $this->addSql('ALTER TABLE job_search_result_monster_se_swe DROP FOREIGN KEY FK_7194A39EBF396760');
        $this->addSql('ALTER TABLE job_search_result_jobs_in_stockholm_com_swe DROP FOREIGN KEY FK_7194A39EBF396761');
        $this->addSql('ALTER TABLE job_search_result_no_indeed_nor DROP FOREIGN KEY FK_7194A39EBF396762');
        $this->addSql('ALTER TABLE job_search_result_jobb_safari_no_nor DROP FOREIGN KEY FK_7194A39EBF396763');
        $this->addSql('ALTER TABLE job_search_result_jobs_in_norway_com_nor DROP FOREIGN KEY FK_7194A39EBF396764');
        $this->addSql('ALTER TABLE job_search_result_jooble_org_no_nor DROP FOREIGN KEY FK_7194A39EBF396765');

        $this->addSql('DROP TABLE job_search_result_info_jobs_net_esp');
        $this->addSql('DROP TABLE job_search_result_info_elempleo_com_esp');
        $this->addSql('DROP TABLE job_search_result_elempleo_com_esp');
        $this->addSql('DROP TABLE job_search_result_talent_esp');
        $this->addSql('DROP TABLE job_search_result_es_jooble_org_esp');
        $this->addSql('DROP TABLE job_search_result_es_indeed_esp');
        $this->addSql('DROP TABLE job_search_result_jooble_se_org_swe');
        $this->addSql('DROP TABLE job_search_result_jobb_safari_se_swe');
        $this->addSql('DROP TABLE job_search_result_jobb_guru_se_swe');
        $this->addSql('DROP TABLE job_search_result_jobb_blocket_se_swe');
        $this->addSql('DROP TABLE job_search_result_jobland_se_swe');
        $this->addSql('DROP TABLE job_search_result_monster_se_swe');
        $this->addSql('DROP TABLE job_search_result_jobs_in_stockholm_com_swe');
        $this->addSql('DROP TABLE job_search_result_no_indeed_nor');
        $this->addSql('DROP TABLE job_search_result_jobb_safari_no_nor');
        $this->addSql('DROP TABLE job_search_result_jobs_in_norway_com_nor');
        $this->addSql('DROP TABLE job_search_result_jooble_org_no_nor');
    }
}
