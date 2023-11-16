<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector\SymfonyCommand;

use ADS\Bundle\EventEngineBundle\Projector\WriteModelStreamProjection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('event-engine:projections:run')]
final class RunProjections extends Command
{
    public function __construct(private readonly WriteModelStreamProjection $projection)
    {
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
