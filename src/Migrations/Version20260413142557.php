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

final class Version20260413142557 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Add tracking configuration columns in 'sylius_channel' table.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sylius_channel ADD gally_use_sylius_endpoint_tracking TINYINT(1) DEFAULT 1 NOT NULL, ADD gally_tracking_active TINYINT(1) DEFAULT 1 NOT NULL, ADD gally_uid_cookie_lifetime INT UNSIGNED DEFAULT 3600 NOT NULL, ADD gally_vid_cookie_lifetime INT UNSIGNED DEFAULT 31536000 NOT NULL, CHANGE gally_active gally_active TINYINT(1) DEFAULT 0 NOT NULL, CHANGE gally_product_index_batch_size gally_product_index_batch_size INT DEFAULT 50 NOT NULL, CHANGE gally_category_index_batch_size gally_category_index_batch_size INT DEFAULT 50 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sylius_channel DROP gally_use_sylius_endpoint_tracking, DROP gally_tracking_active, DROP gally_vid_cookie_lifetime, CHANGE gally_active gally_active TINYINT(1) NOT NULL, CHANGE gally_product_index_batch_size gally_product_index_batch_size INT NOT NULL, CHANGE gally_category_index_batch_size gally_category_index_batch_size INT NOT NULL');
    }
}
