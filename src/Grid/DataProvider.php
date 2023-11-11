<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Grid;

use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Data\DataSourceProviderInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Filtering\FiltersApplicatorInterface;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Grid\Sorting\SorterInterface;

final class DataProvider implements DataProviderInterface
{
    public function __construct(
        private DataSourceProviderInterface $dataSourceProvider,
        private FiltersApplicatorInterface $filtersApplicator,
        private SorterInterface $sorter,
        private ChannelContextInterface $channelContext
    ) {
    }

    public function getData(Grid $grid, Parameters $parameters)
    {
        if ($grid->getCode() === 'sylius_shop_product') {
            $channel = $this->channelContext->getChannel();
            if (($channel instanceof GallyChannelInterface) && ($channel->getGallyActive())) {
                $dataSource = $this->dataSourceProvider->getDataSource($grid, $parameters);

                $this->filtersApplicator->apply($dataSource, $grid, $parameters);

                return $dataSource->getData($parameters);
            }
        }

        // by default use Sylius' implementation of the data provider
        $dataProvider = new \Sylius\Component\Grid\Data\DataProvider(
            $this->dataSourceProvider,
            $this->filtersApplicator,
            $this->sorter,
        );
        return $dataProvider->getData($grid, $parameters);
    }
}
