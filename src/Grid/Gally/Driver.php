<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Grid\Gally;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Gally\SyliusPlugin\Model\GallyChannelInterface;
use Gally\SyliusPlugin\Search\Adapter;
use Sylius\Bundle\GridBundle\Doctrine\ORM\DataSource as ORMDataSource;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Data\DriverInterface;
use Sylius\Component\Grid\Parameters;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class Driver implements DriverInterface
{
    public const NAME = 'gally/rest';

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private Adapter $adapter,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getDataSource(array $configuration, Parameters $parameters): DataSourceInterface
    {
        if (!array_key_exists('class', $configuration)) {
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

        $arguments = isset($configuration['repository']['arguments']) ? array_values($configuration['repository']['arguments']) : [];
        $method = $configuration['repository']['method'];

        $queryBuilder = $repository->$method(...$arguments);

        $channel = $configuration['repository']['arguments']['channel'];
        if (($channel instanceof GallyChannelInterface) && $channel->getGallyActive()) {
            return new DataSource(
                $queryBuilder,
                $this->adapter,
                $this->eventDispatcher,
                $configuration['repository']['arguments']['channel'],
                $configuration['repository']['arguments']['taxon'],
                $configuration['repository']['arguments']['locale']
            );
        }

        // use Sylius' default Doctrine ORM implementation
        return new ORMDataSource($queryBuilder, $fetchJoinCollection, $useOutputWalkers);
    }
}
