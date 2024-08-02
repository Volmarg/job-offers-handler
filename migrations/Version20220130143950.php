<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220130143950 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE company_branch ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE company_branch ADD CONSTRAINT FK_86197F6A979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_86197F6A979B1AD6 ON company_branch (company_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE company_branch DROP FOREIGN KEY FK_86197F6A979B1AD6');
        $this->addSql('DROP INDEX IDX_86197F6A979B1AD6 ON company_branch');
        $this->addSql('ALTER TABLE company_branch DROP company_id');
    }
}
