<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Event;

use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched once per channel/locale batch during product indexation, so a project
 * can add custom fields or override existing ones (e.g. price) in the documents
 * about to be sent to Gally, using the already-loaded product collection.
 */
final class ProductDocumentsIndexEvent extends Event
{
    /** @var array<int|string, array> */
    private array $documents;

    /**
     * @param iterable<ProductInterface> $products
     * @param array<int|string, array> $documents keyed by product id, mutable in place
     */
    public function __construct(
        private iterable $products,
        array &$documents,
    ) {
        $this->documents = &$documents;
    }

    /**
     * @return iterable<ProductInterface>
     */
    public function getProducts(): iterable
    {
        return $this->products;
    }

    /**
     * @return array<int|string, array>
     */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function setDocument(int|string $productId, array $document): void
    {
        $this->documents[$productId] = $document;
    }
}
