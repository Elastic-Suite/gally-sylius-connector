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

namespace Gally\SyliusPlugin\Search;

use Gally\Sdk\Entity\Metadata;
use Gally\Sdk\GraphQl\Request;
use Gally\Sdk\GraphQl\Response;
use Gally\Sdk\Service\SearchManager;
use Gally\SyliusPlugin\Event\SearchRequestContextEvent;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Perform search operations on Gally index and return array of Sylius products.
 */
class Finder
{
    public function __construct(
        private SearchManager $searchManager,
        private CatalogProvider $catalogProvider,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getAutocompleteResults(
        string $query,
        int $resultLimit,
        string $metadata,
        array $fields,
    ): Response {
        $context = new SearchRequestContextEvent();
        $this->eventDispatcher->dispatch($context, 'gally.search.build_request');

        $request = new Request(
            $this->catalogProvider->getLocalizedCatalog(),
            new Metadata($metadata),
            true,
            $fields,
            1,
            $resultLimit,
            null,
            $query,
            [],
            null,
            null,
            $context->getPriceGroupId()
        );

        return $this->searchManager->search($request);
    }
}
