<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240627164328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed api_user and ';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO `api_user` (`id`, `username`, `roles`) VALUES
            (1,	'admin',	'[\"ROLE_USER\"]'),
            (2,	'jooblo',	'[\"ROLE_USER\"]'),
            (3,	'webhook',	'[\"ROLE_USER\"]');
        ");

        $this->addSql("
            INSERT INTO `service_state` (`id`, `created`, `modified`, `name`, `active`) VALUES
            (1,	'2023-01-25 16:51:13',	'2023-08-03 18:15:51',	'Crunchbase',	1);
        ");

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
