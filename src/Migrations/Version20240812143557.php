<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240812143557 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE gally_configuration (id enum('1') NOT NULL, base_url VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE `UTF8_unicode_ci` ENGINE = InnoDB COMMENT ='The ENUM(1) construct as primary key is used to prevent that more than one row can be entered to the table'");
        $this->addSql("INSERT INTO gally_configuration (id, base_url, username, password) VALUES (1, 'https://example.com', 'example', 'example')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE gally_configuration");
    }
}
