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

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Gally\Sdk\Service\SearchManager;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Sylius\Bundle\GridBundle\Doctrine\ORM\DataSource as ORMDataSource;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Data\DriverInterface;
use Sylius\Component\Grid\Parameters;
use Sylius\Component\Locale\Model\Locale;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class Driver implements DriverInterface
{
    public const NAME = 'gally/rest';

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private CatalogProvider $catalogProvider,
        private SearchManager $searchManager,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getDataSource(array $configuration, Parameters $parameters): DataSourceInterface
    {
        if (!\array_key_exists('class', $configuration)) {
            throw new \InvalidArgumentException('"class" must be configured.');
        }

        if (!isset($configuration['repository']['method'])) {
            throw new \InvalidArgumentException('"repository.method" must be configured.');
        }

        /** @var ObjectManager $manager */
        $manager = $this->managerRegistry->getManagerForClass($configuration['class']);

        /** @var EntityRepository $repository */
        $repository = $manager->getRepository($configuration['class']);

        $fetchJoinCollection = $configuration['pagination']['fetch_join_collection'] ?? true;
        $useOutputWalkers = $configuration['pagination']['use_output_walkers'] ?? true;

        $arguments = isset($configuration['repository']['arguments']) ?
            array_values($configuration['repository']['arguments']) : [];
        $method = $configuration['repository']['method'];

        $queryBuilder = $repository->{$method}(...$arguments);

        /** @var ChannelInterface $channel */
        $channel = $configuration['repository']['arguments']['channel'];
        $localeCode = $configuration['repository']['arguments']['locale'];

        /** @var EntityRepository $localeRepository */
        $localeRepository = $manager->getRepository(Locale::class);
        $locale = $localeRepository->findOneBy(['code' => $localeCode]);
        if (($channel instanceof GallyChannelInterface) && $channel->getGallyActive()) {
            $currentLocalizedCatalog = $this->catalogProvider->buildLocalizedCatalog($channel, $locale);

            return new DataSource(
                $queryBuilder,
                $this->searchManager,
                $this->eventDispatcher,
                $currentLocalizedCatalog,
                $configuration['repository']['arguments']['taxon'],
            );
        }

        // use Sylius' default Doctrine ORM implementation
        return new ORMDataSource($queryBuilder, $fetchJoinCollection, $useOutputWalkers);
    }
}
