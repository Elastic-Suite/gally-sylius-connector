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
use Gally\Sdk\Entity\LocalizedCatalog;
use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\GraphQl\Request;
use Gally\Sdk\Service\SearchManager;
use Gally\SyliusPlugin\Event\GridFilterUpdateEvent;
use Gally\SyliusPlugin\Search\Aggregation\AggregationBuilder;
use Gally\SyliusPlugin\Search\Result;
use Pagerfanta\Adapter\AdapterInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Grid\Parameters;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @implements AdapterInterface<ProductInterface>
 */
class PagerfantaGallyAdapter implements AdapterInterface
{
    private ?Result $gallyResult = null;

    public function __construct(
        private QueryBuilder $queryBuilder,
        private SearchManager $searchManager,
        private EventDispatcherInterface $eventDispatcher,
        private LocalizedCatalog $currentLocalizedCatalog,
        private TaxonInterface $taxon,
        private array $filters,
        private Parameters $parameters,
    ) {
    }

    public function getAggregations(): array
    {
        if (null === $this->gallyResult) {
            return [];
        }

        return $this->gallyResult->getAggregations();
    }

    public function getNbResults(): int
    {
        if (null === $this->gallyResult) {
            return 1;
        }

        return max($this->gallyResult->getTotalResultCount(), 0);
    }

    public function getSlice(int $offset, int $length): iterable
    {
        /** @var array<string, array<string, string>> $criteria */
        $criteria = $this->parameters->get('criteria', []);
        $search = (isset($criteria['search'], $criteria['search']['value'])) ? $criteria['search']['value'] : '';
        /** @var array<string> $sorting */
        $sorting = $this->parameters->get('sorting', []);
        $sortField = array_key_first($sorting);
        $sortDirection = $sorting[$sortField] ?? null;
        /** @var int|string $page */
        $page = $this->parameters->get('page', 1);

        $request = new Request(
            $this->currentLocalizedCatalog,
            new Metadata('product'),
            false,
            ['sku', 'source'],
            (int) $page,
            $length,
            str_replace('/', '_', (string) $this->taxon->getCode()),
            $search,
            $this->filters,
            (string) $sortField,
            $sortDirection
        );
        $response = $this->searchManager->search($request);
        $productNumbers = [];
        /** @var array<string, array<mixed>|string> $productRawData */
        foreach ($response->getCollection() as $productRawData) {
            /** @var string $sku */
            $sku = $productRawData['sku'];
            $productNumbers[$sku] = true;
        }
        /** @var array<array<string, array<string, array<string, string>>|string|bool>> $aggregationsData */
        $aggregationsData  = $response->getAggregations();
        $this->gallyResult = new Result(
            $productNumbers,
            $response->getTotalCount(),
            $offset,
            $response->getItemsPerPage(),
            $response->getSortField(),
            $response->getSortDirection(),
            AggregationBuilder::build($aggregationsData)
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

        /** @var array<ProductInterface> $products */
        $products = $this->queryBuilder->getQuery()->execute();

        return $this->sortProductResults($this->gallyResult->getProductNumbers(), $products);
    }

    /**
     * @param ProductInterface[] $products
     *
     * @return array<(int|string), ProductInterface>
     */
    private function sortProductResults(array $productNumbers, array $products): array
    {
        foreach ($products as $product) {
            /* @var ProductInterface $product */
            $productNumbers[$product->getCode()] = $product;
        }

        /** @var array<(int|string), ProductInterface> $productNumbers */
        return $productNumbers;
    }
}
