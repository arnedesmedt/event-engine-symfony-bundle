<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\SymfonyCommand\Projectors;

use ADS\Bundle\EventEngineBundle\Projector\WriteModelStreamProjection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class RunProjectors extends Command
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected static $defaultName = 'event-engine:projectors:run';

    private WriteModelStreamProjection $projection;

    public function __construct(WriteModelStreamProjection $projection)
    {
        $this->projection = $projection;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Run all projectors');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Run projectors');
        $io->note('This command will run indefinitely.');

        $this->projection->run();

        return Command::SUCCESS;
    }
}
