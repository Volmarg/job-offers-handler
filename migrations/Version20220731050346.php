<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220731050346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE amqp_storage (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, target_class VARCHAR(300) DEFAULT NULL, message VARCHAR(900) NOT NULL, unique_id VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_FF5A3564E3C68343 (unique_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE amqp_storage');
        $this->addSql('DROP INDEX country ON location');
    }
}
