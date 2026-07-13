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

final class Version20260709120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add recommendation configuration columns in 'sylius_channel' table.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sylius_channel ADD gally_product_recommendation_max_size INT DEFAULT 4 NOT NULL, ADD gally_cart_recommendation_type_code VARCHAR(255) DEFAULT NULL, ADD gally_cart_recommendation_max_size INT DEFAULT 4 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sylius_channel DROP gally_product_recommendation_max_size, DROP gally_cart_recommendation_type_code, DROP gally_cart_recommendation_max_size');
    }
}
