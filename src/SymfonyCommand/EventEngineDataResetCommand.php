<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\SymfonyCommand;

use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use EventEngine\DocumentStore\DocumentStore;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\StreamName;
use ReflectionClass;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function preg_match;

class EventEngineDataResetCommand extends Command
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected static $defaultName = 'event-engine:data:reset';

    private EventStore $eventStore;
    private DocumentStore $documentStore;
    /** @var array<class-string> */
    private array $aggregates;
    /** @var array<class-string> */
    private array $projectors;

    /**
     * @param array<class-string> $aggregates
     * @param array<class-string> $projectors
     */
    public function __construct(
        EventStore $eventStore,
        DocumentStore $documentStore,
        array $aggregates,
        array $projectors
    ) {
        $this->eventStore = $eventStore;
        $this->documentStore = $documentStore;
        $this->aggregates = $aggregates;
        $this->projectors = $projectors;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Reset all the streams, projections and document stores');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (
            $this->isProductionEnvironment($_SERVER['APP_ENV'])
            && (
                ! $io->confirm('Resetting the data in production is not a good idea. Are you sure?', false)
                || ! $io->confirm('Are you really sure?', false)
            )
        ) {
            $io->comment('Reset is not executed.');

            return 0;
        }

        /** @var Application $application */
        $application = $this->getApplication();

        $createEventStreams = $application->find('event-engine:event-streams:create');
        $createDocumentStores = $application->find('event-engine:document-stores:create');
        $createProjections = $application->find('event-engine:projections:create');
        $resetProjections = $application->find('event-engine:projections:reset');

        if (! empty($this->projectors)) {
            $createProjections->run($input, $output);
        }

        $createEventStreams->run($input, $output);
        $createDocumentStores->run($input, $output);

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

        if (! empty($this->projectors)) {
            $createProjections->run($input, $output);
            $resetProjections->run($input, $output);
        }

        $io->comment('Reset executed.');

        return 0;
    }

    private function isProductionEnvironment(string $env): bool
    {
        return ! (bool) preg_match('/(dev(.*)|local|test)/i', $env);
    }
}
