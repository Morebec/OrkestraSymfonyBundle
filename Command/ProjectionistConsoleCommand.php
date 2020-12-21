<?php


namespace Morebec\OrkestraSymfonyBundle\Command;

use LogicException;
use Morebec\Orkestra\EventSourcing\Projecting\ProjectionistInterface;
use Morebec\Orkestra\EventSourcing\Projecting\ProjectionistWorker;
use Morebec\Orkestra\EventSourcing\Projecting\ProjectorInterface;
use Morebec\Orkestra\Worker\WorkerInterface;
use Morebec\Orkestra\Worker\WorkerOptions;
use Morebec\Orkestra\Worker\WorkerWatcherInterface;
use Morebec\OrkestraSymfonyBundle\EventSourcing\Projecting\ProjectorRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * This console command allows to operate the Projectionist.
 * It provides multiple actions:
 * - play: Runs a Projectionist Worker that continuously update the Projectors.
 * - stop: Stops all Projectionist Workers..
 * - reset: Allows to reset projectors.
 * - replay: Allows to replay projectors.
 */
class ProjectionistConsoleCommand extends AbstractInterruptibleConsoleCommand implements WorkerWatcherInterface
{
    private const PID_NAME = '/tmp/orkestra_projectionist_run.pid';

    protected static $defaultName = 'orkestra:projectionist';

    /**
     * @var ProjectionistInterface
     */
    private $projectionist;

    /**
     * @var ProjectorRegistry
     */
    private $projectorRegistry;

    /**
     * @var ProjectionistWorker
     */
    private $worker;

    /**
     * @var SymfonyStyle
     */
    private $io;

    public function __construct(ProjectorRegistry $projectorRegistry, ProjectionistInterface $projectionist)
    {
        parent::__construct();
        $this->projectionist = $projectionist;
        $this->projectorRegistry = $projectorRegistry;
    }

    protected function configure()
    {
        $this
            ->addOption(
                'projector',
                'p',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'A list of projector type names E.g. --projector="projector1" --projector="projector2": Omit this option to run all registered projectors.',
                []
            );

        $this->addOption(
            'stop',
            null,
            InputOption::VALUE_NONE,
            'This option allows to stop all the running workers.'
        );

        $this->addOption(
            'play',
            null,
            InputOption::VALUE_NONE,
            'This option allows to run a Projectionist Worker that continuously update the Projectors.'
        );

        $this->addOption(
            'reset',
            null,
            InputOption::VALUE_NONE,
            'This option allows to reset some projectors.'
        );

        $this->addOption(
            'replay',
            null,
            InputOption::VALUE_NONE,
            'This option allows to replay some projectors.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('Orkestra Projectionist');

        if ($input->getOption('stop')) {
            $this->stopWorkers();
            return self::SUCCESS;
        }

        // Detect Projectors
        $projectorTypeNames = $input->getOption('projector');

        /** @var ProjectorInterface[] $projectors */
        $projectors = [];

        if ($projectorTypeNames) {
            $this->io->writeln('Selected projectors: ' . implode(', ', $projectorTypeNames));
            foreach ($projectorTypeNames as $projectorTypeName) {
                $projector = $this->projectorRegistry->getProjectorByTypeName($projectorTypeName);
                if (!$projector) {
                    throw new LogicException(sprintf('Projector "%s" was not found', $projectorTypeName));
                }
                $projectors[] = $projector;
            }
        } else {
            $projectors = $this->projectorRegistry->getAll();
            $this->io->writeln(sprintf("Selected projectors: all <info>(%s)</info> projectors.", count($projectors)));
            if (!$projectors) {
                $this->io->warning(sprintf("No projectors, aborting ..."));
                return self::SUCCESS;
            }
        }

        if ($input->getOption('replay')) {
            $this->replayProjectors($projectors);
            return self::SUCCESS;
        }

        if ($input->getOption('reset')) {
            $this->resetProjectors($projectors);
            return self::SUCCESS;
        }

        $this->runProjectionistWorker($projectors);

        return self::SUCCESS;
    }

    protected function onInterruption($input, $output): void
    {
        $this->worker->stop();
    }

    public function onBoot(WorkerInterface $worker): void
    {
        $this->io->writeln('Booting worker ...');
    }

    public function onRun(WorkerInterface $worker): void
    {
        pcntl_signal_dispatch();
    }

    public function onShutdown(WorkerInterface $worker): void
    {
        $this->io->writeln('Shutting down worker ...');
    }

    public function onStop(WorkerInterface $worker): void
    {
        $this->io->writeln('Stopping Worker ...');
    }

    protected function stopWorkers(): void
    {
        posix_kill(file_get_contents(self::PID_NAME), SIGTERM);
    }

    /**
     * Runs the Projectionist Worker for the given projectors.
     * @param ProjectorInterface[] $projectors
     */
    protected function runProjectionistWorker(array $projectors): void
    {
        $this->printOperationMode('Play');
        file_put_contents(self::PID_NAME, posix_getpid());

        $workerOptions = new WorkerOptions();
        $workerOptions->maxExecutionTime = 0;

        $this->worker = new ProjectionistWorker(
            $workerOptions,
            $this->projectionist,
            $projectors,
            [$this]
        );

        $this->worker->boot();

        $this->io->writeln('Running Worker ...');
        $this->worker->run();
    }

    /**
     * Resets the given projectors.
     * @param ProjectorInterface[] $projectors
     */
    protected function resetProjectors(array $projectors): void
    {
        $this->printOperationMode('Reset');
        $this->io->writeln('<info>Operation mode: Reset</info>');
        foreach ($projectors as $projector) {
            $this->io->writeln(sprintf('Resetting projector: "%s" ...', $projector::getTypeName()));
            $this->projectionist->resetProjector($projector);
            $this->io->writeln(sprintf('Projector "%s" reset', $projector::getTypeName()));
        }
    }


    /**
     * Replay the given projectors.
     * @param ProjectorInterface[] $projectors
     */
    protected function replayProjectors(array $projectors): void
    {
        $this->printOperationMode('Replay');
        foreach ($projectors as $projector) {
            $this->io->writeln(sprintf('Replaying projector: "%s" ...', $projector::getTypeName()));
            $this->projectionist->replayProjector($projector);
            $this->io->writeln(sprintf('Projector "%s" replayed', $projector::getTypeName()));
        }
    }

    private function printOperationMode(string $mode)
    {
        $this->io->writeln(sprintf("Operation mode: <info>%s</info>", $mode));
    }
}
