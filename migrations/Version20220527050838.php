<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220527050838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result ADD email_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE job_search_result ADD CONSTRAINT FK_6156F0C0A832C1C9 FOREIGN KEY (email_id) REFERENCES email (id)');
        $this->addSql('CREATE INDEX IDX_6156F0C0A832C1C9 ON job_search_result (email_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result DROP FOREIGN KEY FK_6156F0C0A832C1C9');
        $this->addSql('DROP INDEX IDX_6156F0C0A832C1C9 ON job_search_result');
        $this->addSql('ALTER TABLE job_search_result DROP email_id');
    }
}
