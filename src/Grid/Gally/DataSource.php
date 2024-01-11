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

namespace Gally\SyliusPlugin\Grid\Gally;

use Doctrine\ORM\QueryBuilder;
use Gally\SyliusPlugin\Search\Adapter;
use Pagerfanta\Pagerfanta;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;
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
        private Adapter $adapter,
        private EventDispatcherInterface $eventDispatcher,
        private ChannelInterface $channel,
        private TaxonInterface $taxon,
        private string $locale
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
        $page = (int) $parameters->get('page', 1);

        $paginator = new Pagerfanta(
            new PagerfantaGallyAdapter(
                $this->queryBuilder,
                $this->adapter,
                $this->eventDispatcher,
                $this->channel,
                $this->taxon,
                $this->locale,
                $this->filters,
                $parameters
            )
        );
        $paginator->setNormalizeOutOfRangePages(true);
        $paginator->setCurrentPage($page > 0 ? $page : 1);

        return $paginator;
    }
}
