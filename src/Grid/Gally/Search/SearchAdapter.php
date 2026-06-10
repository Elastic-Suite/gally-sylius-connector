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
use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\GraphQl\Request;
use Gally\Sdk\Service\SearchManager;
use Gally\SyliusPlugin\Event\GridFilterUpdateEvent;
use Gally\SyliusPlugin\Grid\Gally\GallyAdapterInterface;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Gally\SyliusPlugin\Search\Aggregation\AggregationBuilder;
use Gally\SyliusPlugin\Search\Result;
use Pagerfanta\Adapter\AdapterInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Grid\Parameters;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @implements AdapterInterface<ProductInterface>
 */
class SearchAdapter implements AdapterInterface, GallyAdapterInterface
{
    private ?Result $gallyResult = null;

    public function getGallyResult(): ?Result
    {
        return $this->gallyResult;
    }

    public function __construct(
        private QueryBuilder $queryBuilder,
        private SearchManager $searchManager,
        private CatalogProvider $catalogProvider,
        private EventDispatcherInterface $eventDispatcher,
        private Parameters $parameters,
        private array $filters,
    ) {
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
        /** @var string $search */
        $search = $this->parameters->get('query', $criteria['search']['value'] ?? '');
        /** @var array<string> $sorting */
        $sorting = $this->parameters->get('sorting', []);
        /** @var string|int $page */
        $page = $this->parameters->get('page', 1);
        $sortField = array_key_first($sorting);
        $sortDirection = $sorting[$sortField] ?? null;
        $page = (int) $page;

        $request = new Request(
            $this->catalogProvider->getLocalizedCatalog(),
            new Metadata('product'),
            false,  // @todo: parameterize
            ['sku', 'source'],
            $page,
            $length,
            null,
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
        $aggregationsData = $response->getAggregations();
        $this->gallyResult = new Result(
            $productNumbers,
            $response->getTotalCount(),
            $offset,
            $response->getItemsPerPage(),
            $response->getSortField(),
            $response->getSortDirection(),
            AggregationBuilder::build($aggregationsData),
            $this->filters,
            $search,
        );

        $this->eventDispatcher->dispatch(new GridFilterUpdateEvent($this->gallyResult), 'gally.grid.configure_filter');

        $this->queryBuilder->andWhere('o.code IN (:code)');
        $this->queryBuilder->setParameter('code', array_keys($this->gallyResult->getProductNumbers()));

        /** @var array<ProductInterface> $products */
        $products = $this->queryBuilder->getQuery()->execute();

        return $this->sortProductResults($this->gallyResult->getProductNumbers(), $products);
    }

    /**
     * @param array<string, true> $productNumbers
     * @param ProductInterface[]  $products
     *
     * @return array<int|string, ProductInterface>
     */
    private function sortProductResults(array $productNumbers, array $products): array
    {
        foreach ($products as $product) {
            /* @var ProductInterface $product */
            $productNumbers[$product->getCode()] = $product;
        }

        // clean up products sent from Gally that do not exist in Sylius anymore
        foreach ($productNumbers as $code => $product) {
            if (!\is_object($product)) {
                unset($productNumbers[$code]);
            }
        }

        /** @var array<int|string, ProductInterface> $productNumbers */
        return $productNumbers;
    }
}
