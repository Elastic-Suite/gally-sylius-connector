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

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Gally\Sdk\Service\SearchManager;
use Gally\SyliusPlugin\Grid\Gally\Search\DataSource as SearchDataSource;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Data\DriverInterface;
use Sylius\Component\Grid\Parameters;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SearchDriver implements DriverInterface
{
    public const NAME = 'gally/search';

    public function __construct(
        private SearchManager $searchManager,
        private CatalogProvider $catalogProvider,
        private EventDispatcherInterface $eventDispatcher,
        private ManagerRegistry $managerRegistry,
    ) {
    }

    public function getDataSource(array $configuration, Parameters $parameters): DataSourceInterface
    {
        if (!\array_key_exists('class', $configuration)) {
            throw new \InvalidArgumentException('"class" must be configured.');
        }

        /** @var ObjectManager $manager */
        // @phpstan-ignore argument.type
        $manager = $this->managerRegistry->getManagerForClass($configuration['class']);

        /** @var ProductRepositoryInterface $repository */
        // @phpstan-ignore varTag.type, argument.templateType, missingType.generics, argument.type
        $repository = $manager->getRepository($configuration['class']);

        // @phpstan-ignore offsetAccess.nonOffsetAccessible
        $method = $configuration['repository']['method'];
        // @phpstan-ignore offsetAccess.nonOffsetAccessible, argument.type
        $arguments = isset($configuration['repository']['arguments']) ? array_values($configuration['repository']['arguments']) : [];

        return new SearchDataSource(
            // @phpstan-ignore argument.type, method.dynamicName
            $repository->{$method}(...$arguments),
            $this->searchManager,
            $this->catalogProvider,
            $this->eventDispatcher
        );
    }
}
