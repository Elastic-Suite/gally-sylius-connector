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

namespace Gally\SyliusPlugin\Grid\Gally\Search;

use Doctrine\ORM\QueryBuilder;
use Gally\Sdk\Service\SearchManager;
use Gally\SyliusPlugin\Grid\Gally\ExpressionBuilder;
use Gally\SyliusPlugin\Grid\Gally\PagerfantaGally;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Data\ExpressionBuilderInterface;
use Sylius\Component\Grid\Parameters;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DataSource implements DataSourceInterface
{
    private ExpressionBuilderInterface $expressionBuilder;
    private array $filters = [];

    public function __construct(
        private QueryBuilder $queryBuilder,
        private SearchManager $searchManager,
        private CatalogProvider $catalogProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
        $this->expressionBuilder = new ExpressionBuilder();
    }

    public function restrict($expression, string $condition = DataSourceInterface::CONDITION_AND): void
    {
        $this->filters[] = $expression;
    }

    public function getExpressionBuilder(): ExpressionBuilderInterface
    {
        return $this->expressionBuilder;
    }

    public function getData(Parameters $parameters)
    {
        /** @var int|string $page */
        $page = $parameters->get('page', 1);
        $page = (int) $page;
        $paginator = new PagerfantaGally(
            new SearchAdapter(
                $this->queryBuilder,
                $this->searchManager,
                $this->catalogProvider,
                $this->eventDispatcher,
                $parameters,
                $this->filters
            )
        );

        $paginator->setNormalizeOutOfRangePages(true);
        $paginator->setCurrentPage($page > 0 ? $page : 1);

        return $paginator;
    }
}
