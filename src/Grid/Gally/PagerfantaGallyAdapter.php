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
use Gally\SyliusPlugin\Event\GridFilterUpdateEvent;
use Gally\SyliusPlugin\Search\Adapter;
use Gally\SyliusPlugin\Search\Result;
use Pagerfanta\Adapter\AdapterInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Grid\Parameters;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PagerfantaGallyAdapter implements AdapterInterface
{
    private ?Result $gallyResult = null;

    public function __construct(
        private QueryBuilder $queryBuilder,
        private Adapter $adapter,
        private EventDispatcherInterface $eventDispatcher,
        private ChannelInterface $channel,
        private TaxonInterface $taxon,
        private string $locale,
        private array $filters,
        private Parameters $parameters
    ) {
    }

    public function getAggregations(): array
    {
        if (null === $this->gallyResult) {
            return [];
        }

        return $this->gallyResult->getAggregations();
    }

    /**
     * {@inheritDoc}
     */
    public function getNbResults(): int
    {
        if (null === $this->gallyResult) {
            return 0;
        }

        return $this->gallyResult->getTotalResultCount();
    }

    /**
     * {@inheritDoc}
     */
    public function getSlice(int $offset, int $length): iterable
    {
        $offset = $offset > 0 ? $offset : 1;

        $criteria = $this->parameters->get('criteria', []);
        $search = (isset($criteria['search'], $criteria['search']['value'])) ? $criteria['search']['value'] : '';

        $this->gallyResult = $this->adapter->search(
            $this->channel,
            $this->taxon,
            $this->locale,
            $this->filters,
            $this->parameters->get('sorting', []),
            $search,
            $offset,
            $length
        );

        $this->eventDispatcher->dispatch(new GridFilterUpdateEvent($this->gallyResult), 'gally.grid.configure_filter');

        // get rid of the where condition to not limit the product query to the currently active taxon as some
        // products from Gally might not be part of that taxon (e.g. products in virtual categories defined in Gally)
        $this->queryBuilder->resetDQLPart('where');

        // manually add the "missing" query parameters again to make the query work. Since query parameters cannot
        // be removed from the DQL query :taxonLeft, :taxonRight, and :taxonRoot parameter have to be added again in
        // a way that the expression always evaluates to true to get "ignored"
        $this->queryBuilder->andWhere(':taxonLeft < :taxonRight');
        $this->queryBuilder->andWhere(':taxonRoot = :taxonRoot');
        $this->queryBuilder->andWhere(':channel MEMBER OF o.channels');
        $this->queryBuilder->andWhere('o.enabled = :enabled');
        $this->queryBuilder->andWhere('o.code IN (:code)');
        $this->queryBuilder->setParameter('code', array_keys($this->gallyResult->getProductNumbers()));

        $products = $this->queryBuilder->getQuery()->execute();

        return $this->sortProductResults($this->gallyResult->getProductNumbers(), $products);
    }

    /**
     * @param ProductInterface[] $products
     */
    private function sortProductResults(array $productNumbers, array $products): array
    {
        foreach ($products as $product) {
            /** @var ProductInterface $product */
            $productNumbers[$product->getCode()] = $product;
        }

        return $productNumbers;
    }
}
