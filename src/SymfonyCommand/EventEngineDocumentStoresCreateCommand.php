<?php

declare(strict_types=1);

namespace ADS\Bundle\EventEngineBundle\SymfonyCommand;

use ADS\Bundle\EventEngineBundle\Util\EventEngineUtil;
use EventEngine\DocumentStore\DocumentStore;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('event-engine:document-stores:create')]
class EventEngineDocumentStoresCreateCommand extends Command
{
    /**
     * @param array<class-string> $aggregates
     */
    public function __construct(
        private DocumentStore $documentStore,
        private array $aggregates
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Create all the document stores.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->aggregates as $aggregate) {
            $reflectionClass = new ReflectionClass($aggregate);
            $documentStore = EventEngineUtil::fromAggregateNameToDocumentStoreName($reflectionClass->getShortName());

            if ($this->documentStore->hasCollection($documentStore)) {
                continue;
            }

            $this->documentStore->addCollection($documentStore);
        }

        return 0;
    }
}
