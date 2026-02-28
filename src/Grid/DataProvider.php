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

namespace Gally\SyliusPlugin\Grid;

use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Data\DataSourceProviderInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Filtering\FiltersApplicatorInterface;
use Sylius\Component\Grid\Parameters;

final class DataProvider implements DataProviderInterface
{
    public function __construct(
        private DataProviderInterface $dataProvider,
        private DataSourceProviderInterface $dataSourceProvider,
        private FiltersApplicatorInterface $filtersApplicator,
        private ChannelContextInterface $channelContext,
    ) {
    }

    public function getData(Grid $grid, Parameters $parameters)
    {
        if ('sylius_shop_product' === $grid->getCode() || 'gally_shop_product_search' === $grid->getCode()) {
            $channel = $this->channelContext->getChannel();
            if (($channel instanceof GallyChannelInterface) && $channel->getGallyActive()) {
                $dataSource = $this->dataSourceProvider->getDataSource($grid, $parameters);

                $this->filtersApplicator->apply($dataSource, $grid, $parameters);

                return $dataSource->getData($parameters);
            }
        }

        return $this->dataProvider->getData($grid, $parameters);
    }
}
