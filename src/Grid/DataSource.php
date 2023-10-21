<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Grid;

use Doctrine\ORM\QueryBuilder;
use Gally\SyliusPlugin\Search\Adapter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Data\ExpressionBuilderInterface;
use Sylius\Component\Grid\Parameters;

final class DataSource implements DataSourceInterface
{
    private ExpressionBuilderInterface $expressionBuilder;
    private array $filters = [];

    public function __construct(
        private QueryBuilder $queryBuilder,
        private Adapter $adapter,
        private ChannelInterface $channel,
        private TaxonInterface $taxon,
        private string $locale
    ) {
        $this->expressionBuilder = new ExpressionBuilder();
    }

    public function restrict($expression, string $condition = DataSourceInterface::CONDITION_AND): void
    {
        $this->filters += $expression;
    }

    public function getExpressionBuilder(): ExpressionBuilderInterface
    {
        return $this->expressionBuilder;
    }

    public function getData(Parameters $parameters)
    {
        $page = (int) $parameters->get('page', 1);

        $paginator = new Pagerfanta(
            new PagerfantaGallyAdapter(
                $this->queryBuilder,
                $this->adapter,
                $this->channel,
                $this->taxon,
                $this->locale,
                $parameters
            )
        );
        $paginator->setNormalizeOutOfRangePages(true);
        $paginator->setCurrentPage($page > 0 ? $page : 1);

        return $paginator;
    }
}

