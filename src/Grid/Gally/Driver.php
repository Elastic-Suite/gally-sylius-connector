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
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Gally\Sdk\Service\SearchManager;
use Gally\SyliusPlugin\Indexer\Provider\CatalogProvider;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Sylius\Bundle\GridBundle\Doctrine\ORM\DataSource as ORMDataSource;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;
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
        /** @var array{class: class-string, repository: array{method: string, arguments?: array<string, mixed>}, pagination?: array{fetch_join_collection?: bool, use_output_walkers?: bool}} $configuration */
        // @phpstan-ignore function.alreadyNarrowedType
        if (!\array_key_exists('class', $configuration)) {
            throw new \InvalidArgumentException('"class" must be configured.');
        }

        // @phpstan-ignore isset.offset
        if (!isset($configuration['repository']['method'])) {
            throw new \InvalidArgumentException('"repository.method" must be configured.');
        }

        /** @var class-string $class */
        $class = $configuration['class'];
        /** @var ObjectManager $manager */
        $manager = $this->managerRegistry->getManagerForClass($class);

        /** @var EntityRepository $repository */
        // @phpstan-ignore-next-line
        $repository = $manager->getRepository($class);

        // @phpstan-ignore cast.useless
        $fetchJoinCollection = (bool) ($configuration['pagination']['fetch_join_collection'] ?? true);
        // @phpstan-ignore cast.useless
        $useOutputWalkers = (bool) ($configuration['pagination']['use_output_walkers'] ?? true);

        // @phpstan-ignore offsetAccess.notFound
        $arguments = isset($configuration['repository']['arguments']) ?
            array_values($configuration['repository']['arguments']) : [];
        $method = $configuration['repository']['method'];

        /** @var QueryBuilder $queryBuilder */
        // @phpstan-ignore method.dynamicName
        $queryBuilder = $repository->{$method}(...$arguments);

        /** @var ChannelInterface $channel */
        // @phpstan-ignore offsetAccess.notFound
        $channel = $configuration['repository']['arguments']['channel'];
        // @phpstan-ignore offsetAccess.notFound
        $localeCode = $configuration['repository']['arguments']['locale'];

        /** @var EntityRepository<Locale> $localeRepository */
        $localeRepository = $manager->getRepository(Locale::class);
        $locale = $localeRepository->findOneBy(['code' => $localeCode]) ?? $channel->getDefaultLocale();
        if (null === $locale) {
            throw new \LogicException(sprintf('Missing default locale on channel %s', $channel->getName()));
        }
        if (($channel instanceof GallyChannelInterface) && $channel->getGallyActive()) {
            $currentLocalizedCatalog = $this->catalogProvider->buildLocalizedCatalog($channel, $locale);
            /** @var TaxonInterface $taxon */
            // @phpstan-ignore offsetAccess.notFound
            $taxon = $configuration['repository']['arguments']['taxon'];

            return new DataSource(
                $queryBuilder,
                $this->searchManager,
                $this->eventDispatcher,
                $currentLocalizedCatalog,
                $taxon,
            );
        }

        // use Sylius' default Doctrine ORM implementation
        return new ORMDataSource($queryBuilder, $fetchJoinCollection, $useOutputWalkers);
    }
}
