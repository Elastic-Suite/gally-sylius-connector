<?php
/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Gally to newer versions in the future.
 *
 * @package   Gally
 * @author    Stephan Hochdörfer <S.Hochdoerfer@bitexpert.de>, Gally Team <elasticsuite@smile.fr>
 * @copyright 2022-present Smile
 * @license   Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Gally\SyliusPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261014100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Move recommendation configuration from 'sylius_channel' to 'gally_configuration' table.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE gally_configuration ADD product_recommendation_type_codes JSON NOT NULL, ADD product_recommendation_max_size INT DEFAULT 4 NOT NULL, ADD cart_recommendation_type_codes JSON NOT NULL, ADD cart_recommendation_max_size INT DEFAULT 4 NOT NULL');
        $this->addSql("UPDATE gally_configuration SET product_recommendation_type_codes = '[]', cart_recommendation_type_codes = '[]'");
        $this->addSql('ALTER TABLE sylius_channel DROP gally_product_recommendation_max_size, DROP gally_cart_recommendation_type_code, DROP gally_cart_recommendation_max_size');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sylius_channel ADD gally_product_recommendation_max_size INT DEFAULT 4 NOT NULL, ADD gally_cart_recommendation_type_code VARCHAR(255) DEFAULT NULL, ADD gally_cart_recommendation_max_size INT DEFAULT 4 NOT NULL');
        $this->addSql('ALTER TABLE gally_configuration DROP product_recommendation_type_codes, DROP product_recommendation_max_size, DROP cart_recommendation_type_codes, DROP cart_recommendation_max_size');
    }
}
