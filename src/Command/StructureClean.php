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

use Gally\SyliusPlugin\Synchronizer\AbstractSynchronizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StructureClean extends Command
{
    protected static $defaultName = 'gally:structure:clean';

    /**
     * @param AbstractSynchronizer[] $synchronizers
     */
    public function __construct(
        private iterable $synchronizers
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Remove all entity from gally that not exist anymore on sylius side.')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Really remove the listed entity from the gally.')
            ->addOption('quiet', 'q', InputOption::VALUE_NONE, 'Don\'t list deleted entities.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $isDryRun = !$input->getOption('force');
        $isQuiet = $input->getOption('quiet');

        if ($isDryRun) {
            $output->writeln('<error>Running in dry run mode, add -f to really delete entities from Gally.</error>');
            $output->writeln('');
        }

        foreach ($this->synchronizers as $synchronizer) {
            $time = microtime(true);
            $message = "<comment>Clean {$synchronizer->getEntityClass()}</comment>";
            $output->writeln("$message ...");
            $synchronizer->cleanAll($isDryRun, $isQuiet);
            $time = number_format(microtime(true) - $time, 2);
            $output->writeln("  Cleaned ($time)s\n");
        }

        return 0;
    }
}
