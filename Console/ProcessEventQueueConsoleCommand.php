<?php

namespace Morebec\OrkestraBundle\Console;

use Exception;
use Morebec\Orkestra\EventSourcing\EventStoreChaser;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console Command used to Process the unprocessed events of the Event Queue.
 * @package Morebec\OrkestraBundle\Console
 */
class ProcessEventQueueConsoleCommand extends AbstractDaemonConsoleCommand implements ApplicationConsoleCommandInterface
{
    private const CHECK_INTERVAL = 1;

    protected static $defaultName = 'event:queue:process';

    /**
     * @var EventStoreChaser
     */
    private $eventStoreChaser;

    public function __construct(EventStoreChaser $eventStoreChaser)
    {
        parent::__construct(self::CHECK_INTERVAL, self::$defaultName);
        $this->eventStoreChaser = $eventStoreChaser;
    }

    protected function configure(): void
    {
        $this->enableLocking(true);
    }

    /**
     * {@inheritdoc}
     */
    protected function onInitialization(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Initializing daemon ...');
        $output->writeln('Daemon started');
    }

    protected function executeContinuous(InputInterface $input, OutputInterface $output): void
    {
        $this->eventStoreChaser->process();
    }

    /**
     * {@inheritdoc}
     */
    protected function onError(InputInterface $input, OutputInterface $output, Exception $e): void
    {
        $output->writeln('There was an error:');
        $output->writeln($e->getMessage());
        $output->writeln("{$e->getFile()}:{$e->getLine()}");
    }

    /**
     * {@inheritdoc}
     */
    protected function onInterruption(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Command interrupted');
    }

    /**
     * {@inheritdoc}
     */
    protected function onShutdown(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Shutting down ...');
        $output->writeln('Daemon shutdown');
    }
}
