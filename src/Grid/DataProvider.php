<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Grid;

use Sylius\Component\Grid\Data\DataProviderInterface;
use Sylius\Component\Grid\Data\DataSourceProviderInterface;
use Sylius\Component\Grid\Definition\Grid;
use Sylius\Component\Grid\Filtering\FiltersApplicatorInterface;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Grid\Sorting\SorterInterface;

final class DataProvider implements DataProviderInterface
{
    private DataSourceProviderInterface $dataSourceProvider;

    private FiltersApplicatorInterface $filtersApplicator;

    private SorterInterface $sorter;

    public function __construct(
        DataSourceProviderInterface $dataSourceProvider,
        FiltersApplicatorInterface $filtersApplicator,
        SorterInterface $sorter,
    ) {
        $this->dataSourceProvider = $dataSourceProvider;
        $this->filtersApplicator = $filtersApplicator;
        $this->sorter = $sorter;
    }

    public function getData(Grid $grid, Parameters $parameters)
    {
        $dataSource = $this->dataSourceProvider->getDataSource($grid, $parameters);

        $this->filtersApplicator->apply($dataSource, $grid, $parameters);

        if ($grid->getCode() !== 'sylius_shop_product') {
            $this->sorter->sort($dataSource, $grid, $parameters);
        }

        return $dataSource->getData($parameters);
    }
}

