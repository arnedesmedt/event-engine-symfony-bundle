<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector\SymfonyCommand;

use Prooph\EventStore\Projection\ProjectionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function count;
use function sprintf;

final class ResetProjections extends Command
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected static $defaultName = 'event-engine:projections:reset';

    public function __construct(private ProjectionManager $projectionManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Reset all projectors. This will flag the projections to reset.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Reset projectors');
        $projectionNames = $this->projectionManager->fetchProjectionNames(null);

        $io->note(sprintf('I found %d projectors (streams) to reset', count($projectionNames)));
        foreach ($projectionNames as $projectionName) {
            $io->note('Resetting ' . $projectionName);
            $this->projectionManager->resetProjection($projectionName);
        }

        $io->success('The projections will reset!');

        return Command::SUCCESS;
    }
}
