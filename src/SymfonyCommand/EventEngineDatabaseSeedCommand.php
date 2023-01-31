<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\SymfonyCommand;

use ADS\Bundle\EventEngineBundle\Aggregate\AggregateRoot;
use ADS\Bundle\EventEngineBundle\Flavour\OopMetaDataFlavour;
use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use EventEngine\Aggregate\FlavouredAggregateRoot;
use EventEngine\Aggregate\GenericAggregateRepository;
use EventEngine\DocumentStore\DocumentStore;
use EventEngine\JsonSchema\JsonSchemaAwareRecord;
use EventEngine\Messaging\Message;
use EventEngine\Messaging\MessageBag;
use EventEngine\Persistence\MultiModelStore;
use EventEngine\Runtime\Oop\AggregateAndEventBag;
use EventEngine\Runtime\Oop\FlavourHint;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

use function array_combine;
use function array_fill;
use function array_keys;
use function count;
use function str_contains;
use function trim;

#[AsCommand('event-engine:database:seed', 'Seed the database with events.')]
class EventEngineDatabaseSeedCommand extends Command
{
    public function __construct(
        private readonly OopMetaDataFlavour $flavour,
        private readonly MultiModelStore $eventStore,
        private readonly DocumentStore $documentStore,
        private readonly string $environment,
        private readonly string $projectDir,
        private readonly string $seedPath,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (str_contains($this->environment, 'prod')) {
            $io->comment('You can\'t seed the database in production.');

            return Command::SUCCESS;
        }

        $finder = new Finder();
        $finder
            ->files()
            ->in($this->projectDir . '/' . trim($this->seedPath, '/') . '/' . $this->environment)
            ->name('*.yaml');

        foreach ($finder as $file) {
            /** @var array<string, array<string, array<string, array<int, array<string, mixed>>>>>  $entityEvents */
            $entityEvents = Yaml::parseFile($file->getRealPath());

            /** @var class-string<AggregateRoot<JsonSchemaAwareRecord>> $entityClass */
            foreach ($entityEvents as $entityClass => $eventsPerAggregate) {
                $aggregateRepository = new GenericAggregateRepository(
                    $this->flavour,
                    $this->eventStore,
                    EventEngineUtil::fromAggregateClassToStreamName($entityClass),
                    $this->documentStore,
                    EventEngineUtil::fromAggregateClassToDocumentStoreName($entityClass),
                );

                foreach ($eventsPerAggregate as $aggregateId => $eventsPerEventName) {
                    $eventClasses = array_keys($eventsPerEventName);
                    $eventApplyMap = array_combine(
                        $eventClasses,
                        array_fill(0, count($eventsPerEventName), [FlavourHint::class, 'useAggregate']),
                    );

                    $aggregate = new FlavouredAggregateRoot($aggregateId, $entityClass, $eventApplyMap, $this->flavour);
                    $aggregateAndEventBag = null;
                    foreach ($eventsPerEventName as $eventClass => $payloads) {
                        foreach ($payloads as $payload) {
                            $event = $eventClass::fromArray($payload);
                            if ($aggregateAndEventBag === null) {
                                $aggregateAndEventBag = $event = new AggregateAndEventBag(
                                    $entityClass::createForSeed(),
                                    $event,
                                );
                            }

                            $aggregate->recordThat(
                                new MessageBag(
                                    $eventClass,
                                    Message::TYPE_EVENT,
                                    $event,
                                ),
                            );
                        }
                    }

                    $aggregateRepository->saveAggregateRoot($aggregate);
                }
            }
        }

        return Command::SUCCESS;
    }
}
