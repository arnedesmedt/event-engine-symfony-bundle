<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector\SymfonyCommand;

use ADS\Bundle\EventEngineBundle\Projector\WriteModelStreamProjection;
use Prooph\EventStore\Projection\ProjectionManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ResetProjections extends Command
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected static $defaultName = 'event-engine:projections:reset';
    private ProjectionManager $projectionManager;

    public function __construct(ProjectionManager $projectionManager)
    {
        $this->projectionManager = $projectionManager;
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
        $io->note('Resetting ' . WriteModelStreamProjection::NAME);

        $this->projectionManager->resetProjection(WriteModelStreamProjection::NAME);

        $io->success('The projections will reset!');

        return Command::SUCCESS;
    }
}
