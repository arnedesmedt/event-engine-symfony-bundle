<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\Projector\SymfonyCommand;

use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class EventEngineProjectionsCreateCommand extends Command
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected static $defaultName = 'event-engine:projections:create';

    public function __construct(private PDO $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Create the projections table.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->connection->exec(
            'CREATE TABLE IF NOT EXISTS projections (
  no BIGSERIAL,
  name VARCHAR(150) NOT NULL,
  position JSONB,
  state JSONB,
  status VARCHAR(28) NOT NULL,
  locked_until CHAR(26),
  PRIMARY KEY (no),
  UNIQUE (name)
);'
        );

        return Command::SUCCESS;
    }
}
