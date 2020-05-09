<?php

namespace Morebec\OrkestraBundle\Console;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The AbstractDaemonConsoleCommand Class is used to easily create Daemon Console Commands.
 * It handles everything related to locking in order to prevent multiple daemons to be ran at the same time.
 * If you wish to disable the locking and allow multiple instance of this command to be ran at the same time,
 * In the constructor call the following method with false as a parameter: `enableLocking(false)`
 * @package Morebec\OrkestraBundle\Console
 */
abstract class AbstractDaemonConsoleCommand extends Command
{
    use LockableTrait;

    /** @var bool Flag used to indicate if the execution of the command is currently interrupted */
    protected $interrupted = false;

    /**
     * @var int sleep time between `run` method calls
     */
    protected $interval;

    /**
     * Indicates if locking should enabled for this command, forcing only a single process to execute it at a time.
     *
     * @var bool
     */
    protected $lockingEnabled;

    public function __construct(int $interval, string $name = null)
    {
        parent::__construct($name);
        $this->interval = $interval;
        $this->lockingEnabled = false;
    }

    public function onSig(InputInterface $input, OutputInterface $output): void
    {
        $this->interrupted = true;
        $this->onInterruption($input, $output);
    }

    protected function enableLocking(bool $lockingEnabled = true): void
    {
        $this->lockingEnabled = $lockingEnabled;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        pcntl_async_signals(true);

        $interruptionCallback = function () use ($input, $output) {
            $this->onSig($input, $output);
        };

        pcntl_signal(SIGINT, $interruptionCallback);
        pcntl_signal(SIGTERM, $interruptionCallback);

        if ($this->lockingEnabled && !$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        $this->onInitialization($input, $output);
        do {
            try {
                $this->executeContinuous($input, $output);
            } catch (Exception $e) {
                $this->onError($input, $output, $e);
            }

            pcntl_signal_dispatch();
            sleep($this->interval);
        } while (!$this->interrupted);

        if ($this->lockingEnabled) {
            $this->release();
        }

        $this->onShutdown($input, $output);

        return 0;
    }

    /**
     * Called at the start of the command.
     */
    abstract protected function onInitialization(InputInterface $input, OutputInterface $output): void;

    /**
     * Called any time the command encounters an error.
     */
    abstract protected function onError(InputInterface $input, OutputInterface $output, Exception $e): void;

    /**
     * Called when the command is interrupted (CTRL + C).
     */
    abstract protected function onInterruption(InputInterface $input, OutputInterface $output): void;

    /**
     * Called when the command is shutting down.
     */
    abstract protected function onShutdown(InputInterface $input, OutputInterface $output): void;

    /**
     * Method called continuously.
     */
    abstract protected function executeContinuous(InputInterface $input, OutputInterface $output): void;
}
