<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\SymfonyCommand;

use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use EventEngine\DocumentStore\DocumentStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\StreamName;
use ReflectionClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function str_contains;

#[AsCommand('event-engine:database:clear', 'clear all the streams, projections and document stores.')]
class EventEngineDatabaseClearCommand extends Command
{
    /** @param array<class-string> $aggregates */
    public function __construct(
        private readonly EventStore $eventStore,
        private readonly DocumentStore $documentStore,
        private readonly array $aggregates,
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (str_contains($this->environment, 'prod')) {
            $io->comment('You can\'t reset the database in production.');

            return Command::SUCCESS;
        }

        /** @var Application $application */
        $application = $this->getApplication();

        $createEventStreams = $application->find('event-engine:event-streams:create');
        $createDocumentStores = $application->find('event-engine:document-stores:create');
        $createProjections = $application->find('event-engine:projections:create');
        $resetProjections = $application->find('event-engine:projections:reset');

        $createEventStreams->run($input, $output);
        $createDocumentStores->run($input, $output);
        $createProjections->run($input, $output);

        foreach ($this->aggregates as $aggregate) {
            $reflectionClass = new ReflectionClass($aggregate);
            $shortAggregateName = $reflectionClass->getShortName();
            $documentStore = EventEngineUtil::fromAggregateNameToDocumentStoreName($shortAggregateName);
            $streamName = EventEngineUtil::fromAggregateNameToStreamName($shortAggregateName);
            $streamNameObject = new StreamName($streamName);

            if ($this->eventStore->hasStream($streamNameObject)) {
                $this->eventStore->delete($streamNameObject);
            }

            if (! $this->documentStore->hasCollection($documentStore)) {
                continue;
            }

            $this->documentStore->dropCollection($documentStore);
        }

        $createEventStreams->run($input, $output);
        $createDocumentStores->run($input, $output);
        $createProjections->run($input, $output);
        $resetProjections->run($input, $output);

        $io->comment('Reset executed.');

        return Command::SUCCESS;
    }
}
