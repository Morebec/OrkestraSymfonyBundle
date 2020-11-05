<?php


namespace Morebec\OrkestraSymfonyBundle\Command;

use Morebec\Orkestra\Messaging\Scheduling\DomainMessageSchedulerWorker;
use Morebec\Orkestra\Worker\WorkerInterface;
use Morebec\Orkestra\Worker\WorkerWatcherInterface;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command running the Worker scheduler.
 */
class RunDomainMessageSchedulerWorkerConsoleCommand extends AbstractInterruptibleConsoleCommand implements WorkerWatcherInterface
{
    use LockableTrait;

    private const PID_FILE_NAME = '/tmp/orkestra_message_scheduler.pid';

    protected static $defaultName = 'orkestra:messaging:scheduler';

    /**
     * @var DomainMessageSchedulerWorker
     */
    private $worker;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(DomainMessageSchedulerWorker $worker)
    {
        parent::__construct();
        $this->worker = $worker;
        $this->worker->addWatcher($this);
    }

    protected function configure()
    {
        parent::configure();
        $this->addOption(
            'stop',
            null,
            InputOption::VALUE_NONE,
            'This option allows to stop the running worker.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        if ($input->getOption('stop')) {
            posix_kill(file_get_contents(self::PID_FILE_NAME), SIGTERM);
            return self::SUCCESS;
        }

        if(!$this->lock()) {
            $output->writeln('Aborting, command already running ...');
            return self::FAILURE;
        }

        // To allow another instance of the command to kill the current instance.
        file_put_contents(self::PID_FILE_NAME, posix_getpid());

        $this->output = $output;

        $this->worker->boot();

        $this->output->writeln('Running worker ...');
        $this->worker->run();

        return self::SUCCESS;
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
        $this->release();
    }

    public function onStop(WorkerInterface $worker): void
    {
        $this->output->writeln('Stopping Worker ...');
    }

    protected function onInterruption($input, $output): void
    {
        $this->worker->stop();
    }
}