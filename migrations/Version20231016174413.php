<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231016174413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_search_result_one_jeune_one_solution_fr (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_search_result_cadremploi_fr (id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job_search_result_one_jeune_one_solution_fr ADD CONSTRAINT FK_F6F0C406BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_cadremploi_fr ADD CONSTRAINT FK_930BC1C9BF396750 FOREIGN KEY (id) REFERENCES job_search_result (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result_one_jeune_one_solution_fr DROP FOREIGN KEY FK_F6F0C406BF396750');
        $this->addSql('ALTER TABLE job_search_result_cadremploi_fr DROP FOREIGN KEY FK_930BC1C9BF396750');
        $this->addSql('DROP TABLE job_search_result_one_jeune_one_solution_fr');
        $this->addSql('DROP TABLE job_search_result_cadremploi_fr');
    }
}
