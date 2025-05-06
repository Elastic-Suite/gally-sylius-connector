<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250415081609 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add table 'gally_configuration' and add some gally columns in 'sylius_channel' table.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE gally_configuration (id INT AUTO_INCREMENT NOT NULL, base_url VARCHAR(255) NOT NULL, check_ssl TINYINT(1) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sylius_channel ADD gally_active TINYINT(1) NOT NULL, ADD gally_product_index_batch_size INT NOT NULL, ADD gally_category_index_batch_size INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE gally_configuration');
        $this->addSql('ALTER TABLE sylius_channel DROP gally_active, DROP gally_product_index_batch_size, DROP gally_category_index_batch_size');
    }
}
