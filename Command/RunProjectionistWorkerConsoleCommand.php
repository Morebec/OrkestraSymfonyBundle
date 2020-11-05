<?php


namespace Morebec\OrkestraSymfonyBundle\Command;

use LogicException;
use Morebec\Orkestra\EventSourcing\Projecting\ProjectionistInterface;
use Morebec\Orkestra\EventSourcing\Projecting\ProjectionistWorker;
use Morebec\Orkestra\Worker\WorkerInterface;
use Morebec\Orkestra\Worker\WorkerOptions;
use Morebec\Orkestra\Worker\WorkerWatcherInterface;
use Morebec\OrkestraSymfonyBundle\EventSourcing\Projecting\ProjectorRegistry;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This console command invokes the Projectionist Worker
 */
class RunProjectionistWorkerConsoleCommand extends AbstractInterruptibleConsoleCommand implements WorkerWatcherInterface
{
    private const PID_NAME = '/tmp/orkestra_projectionist_run.pid';

    protected static $defaultName = 'orkestra:messaging:projectionist';

    /**
     * @var ProjectionistInterface
     */
    private $projectionist;

    /**
     * @var ProjectorRegistry
     */
    private $projectorRegistry;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var ProjectionistWorker
     */
    private $worker;

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
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if ($input->getOption('stop')) {
            posix_kill(file_get_contents(self::PID_NAME), SIGTERM);
            return self::SUCCESS;
        }

        $projectorTypeNames = $input->getOption('projector');

        if ($projectorTypeNames) {
            $output->writeln('Selected projectors: ' . implode(', ', $projectorTypeNames));
            $projectors = [];
            foreach ($projectorTypeNames as $projectorTypeName) {
                $projector = $this->projectorRegistry->getProjectorByTypeName($projectorTypeName);
                if(!$projector) {
                    throw new LogicException(sprintf('Projector "%s" was not found', $projectorTypeName));
                }
                $projectors[] = $projector;
            }
        } else {
            $output->writeln('Selected projectors: all projectors.');
            $projectors = $this->projectorRegistry->getAll();
        }
        file_put_contents(self::PID_NAME, posix_getpid());

        $this->output = $output;

        $workerOptions = new WorkerOptions();
        $workerOptions->maxExecutionTime = 0;

        $this->worker = new ProjectionistWorker(
            $workerOptions,
            $this->projectionist,
            $projectors,
            [$this]
        );

        $this->worker->boot();

        $output->writeln('Running Worker ...');
        $this->worker->run();

        return self::SUCCESS;
    }

    protected function onInterruption($input, $output): void
    {
        $this->worker->stop();
    }

    public function onBoot(WorkerInterface $worker): void
    {
        $this->output->writeln('Booting worker ...');
    }

    public function onRun(WorkerInterface $worker): void
    {
        pcntl_signal_dispatch();
    }

    public function onShutdown(WorkerInterface $worker): void
    {
        $this->output->writeln('Shutting down worker ...');
    }

    public function onStop(WorkerInterface $worker): void
    {
        $this->output->writeln('Stopping Worker ...');
    }
}