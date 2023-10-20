<?php

declare(strict_types=1);

namespace Gally\SyliusPlugin\Grid;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Gally\SyliusPlugin\Search\Adapter;
use Sylius\Component\Grid\Data\DataSourceInterface;
use Sylius\Component\Grid\Data\DriverInterface;
use Sylius\Component\Grid\Parameters;

final class Driver implements DriverInterface
{
    public const NAME = 'gally/rest';

    public function __construct(
        private ManagerRegistry $managerRegistry,
        private Adapter $adapter
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

        $arguments = isset($configuration['repository']['arguments']) ? array_values($configuration['repository']['arguments']) : [];
        $method = $configuration['repository']['method'];

        $queryBuilder = $repository->$method(...$arguments);

        return new DataSource(
            $queryBuilder,
            $this->adapter,
            $configuration['repository']['arguments']['channel'],
            $configuration['repository']['arguments']['taxon'],
            $configuration['repository']['arguments']['locale']
        );
    }
}
