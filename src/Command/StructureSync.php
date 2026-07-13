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

namespace Gally\SyliusPlugin\Command;

use Gally\Sdk\Service\StructureSynchonizer;
use Gally\SyliusPlugin\Indexer\Provider\ProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'gally:structure:sync',
    description: 'Synchronize sales channels, entity fields with gally data structure.',
)]
class StructureSync extends Command
{
    /** @var array<string, ProviderInterface[]> */
    protected array $providers = [];

    /** @var array<string, string> */
    protected array $syncMethod = [
        'catalog' => 'syncAllLocalizedCatalogs',
        'sourceField' => 'syncAllSourceFields',
        'sourceFieldOption' => 'syncAllSourceFieldOptions',
        'recommenderType' => 'syncAllRecommenderTypes',
    ];

    /**
     * @param iterable<ProviderInterface> $providers
     */
    public function __construct(
        protected StructureSynchonizer $synchonizer,
        iterable $providers,
    ) {
        parent::__construct();
        foreach ($providers as $provider) {
            $this->providers[$provider->getEntity()][] = $provider;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');

        foreach ($this->syncMethod as $entity => $method) {
            $message = "<comment>Sync $entity</comment>";
            $time = microtime(true);
            $output->writeln("$message ...");
            // @phpstan-ignore method.dynamicName
            $this->synchonizer->{$method}($this->provideAll($entity));
            $time = number_format(microtime(true) - $time, 2);
            $output->writeln("\033[1A$message <info>✔</info> ($time)s");
        }

        $output->writeln('');

        return 0;
    }

    /**
     * Merges the provide() results of every provider registered for the given entity,
     * so several namespaces can contribute to it independently.
     */
    protected function provideAll(string $entity): iterable
    {
        foreach ($this->providers[$entity] ?? [] as $provider) {
            yield from $provider->provide();
        }
    }
}
