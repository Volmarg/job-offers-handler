<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231014154657 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_search_result_jobi_joba_com_fr (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job_search_result_jobi_joba_com_fr ADD CONSTRAINT FK_72387907BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE job_search_result_hello_work_com_fr (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job_search_result_hello_work_com_fr ADD CONSTRAINT FK_72387907BF396752 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result_jobi_joba_com_fr DROP FOREIGN KEY FK_72387907BF396750');
        $this->addSql('DROP TABLE job_search_result_jobi_joba_com_fr');

        $this->addSql('ALTER TABLE job_search_result_hello_work_com_fr DROP FOREIGN KEY FK_72387907BF396752');
        $this->addSql('DROP TABLE job_search_result_hello_work_com_fr');
    }
}
