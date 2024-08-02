<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220815135406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_5E9E89CB5E237E065373C96685E16F6B4118D123 ON location');
        $this->addSql('ALTER TABLE location ADD region VARCHAR(75) DEFAULT NULL, ADD region_code VARCHAR(50) DEFAULT NULL, ADD continent VARCHAR(100) DEFAULT NULL, ADD native_language_city_name VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX country ON location');
        $this->addSql('DROP INDEX UNIQ_5E9E89CB5E237E065373C9666CC70C7C4118D12385E16F6B ON location');
        $this->addSql('ALTER TABLE location CHANGE region region VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE region_code region_code VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE native_language_city_name native_language_city_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE continent continent VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
