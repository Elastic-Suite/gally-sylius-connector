<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Grid;

use Doctrine\ORM\QueryBuilder;
use Gally\SyliusPlugin\Search\Adapter;
use Gally\SyliusPlugin\Search\Result;
use Pagerfanta\Adapter\AdapterInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Grid\Parameters;

class PagerfantaGallyAdapter implements AdapterInterface
{
    private ?Result $gallyResult = null;

    public function __construct(
        private QueryBuilder $queryBuilder,
        private Adapter $adapter,
        private ChannelInterface $channel,
        private TaxonInterface $taxon,
        private string $locale,
        private Parameters $parameters
    ) {
    }

    public function getAggregations(): array
    {
        if ($this->gallyResult === null) {
            return [];
        }

        return $this->gallyResult->getAggregations();
    }

    /**
     * @inheritDoc
     */
    public function getNbResults(): int
    {
        if ($this->gallyResult === null) {
            return 0;
        }

        return $this->gallyResult->getTotalResultCount();
    }

    /**
     * @inheritDoc
     */
    public function getSlice(int $offset, int $length): iterable
    {
        $offset = $offset > 0 ? $offset : 1;

        $this->gallyResult = $this->adapter->search(
            $this->channel,
            $this->taxon,
            $this->locale,
            $this->parameters->get('criteria', []),
            $this->parameters->get('sorting', []),
            $offset,
            $length
        );

        $this->queryBuilder->andWhere('o.code IN (:code)');
        $this->queryBuilder->setParameter('code', array_keys($this->gallyResult->getProductNumbers()));

        $products = $this->queryBuilder->getQuery()->execute();
        return $this->sortProductResults($this->gallyResult->getProductNumbers(), $products);
    }

    /**
     * @param array $productNumbers
     * @param ProductInterface[] $products
     * @return array
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
