<?php

namespace SciloneToolboxBundle\PubSub;

use Exception;
use Google\Cloud\PubSub\Message;
use Psr\Log\LoggerInterface;
use SciloneToolboxBundle\Logger\LoggerFactory;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use SciloneToolboxBundle\PubSub\Exception\LoopException;
use SciloneToolboxBundle\Symfony\Command\AbstractCommand;

use Symfony\Component\Process\Process;
use const SIGINT;
use const SIGQUIT;
use const SIGTERM;

abstract class AbstractPubSubMessagePullerCommand extends AbstractCommand implements SignalableCommandInterface
{
    protected const SUBSCRIBED_SIGNALS = [
        SIGINT,
        SIGQUIT,
        SIGTERM
    ];
    protected const DEFAULT_WORKER_TIMEOUT = 900;
    protected const ACK_DEADLINE = 10;
    protected const TIME_TO_WAIT_BETWEEN_EMPTY_MESSAGE = 5;

    private const OPTION_TIMEOUT = 'timeout';


    protected bool $interruptConsumption = false;
    protected int $workerTimeout = 10;

    public function __construct(
        protected readonly SubscriptionFacade $subscriptionFacade,
        protected readonly string $subscriptionName,
        protected readonly LoggerFactory $loggerFactory,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
    }

    protected function configure()
    {
        parent::configure();

        $this
            ->addOption(
                self::OPTION_TIMEOUT,
                't',
                InputOption::VALUE_REQUIRED,
                'Inactivity until auto kill worker (in secondes)',
                static::DEFAULT_WORKER_TIMEOUT
            );
    }

    public function getSubscribedSignals(): array
    {
        return static::SUBSCRIBED_SIGNALS;
    }

    public function handleSignal(int $signal): void
    {
        $this->interruptConsumption = true;
    }

    protected function shouldBeStopped(): bool
    {
        return $this->interruptConsumption;
    }

    /**
     * @throws \SciloneToolboxBundle\PubSub\Exception\InvalidAckDeadlineTime
     * @throws LoopException
     */
    protected function doExecute(): int {
        $this->onStartLoop();

        $message = $this->subscriptionFacade->pull($this->subscriptionName);
        if ($message instanceof Message === false) {
            $this->onEmptyPull();

            return self::SUCCESS;
        }

        $exitCode = self::FAILURE;
        try {
            $process = new Process($this->getProcessCommand($message));
            $process->start();

            do {
                $this->subscriptionFacade->modifyAckDeadline(
                    $this->subscriptionName,
                    $message,
                    static::ACK_DEADLINE
                );

                sleep(round(static::ACK_DEADLINE/2));
            } while ($process->isRunning());

            $this->postProcess($process);

            $exitCode = $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
        } catch (Exception $e) {
            $this->onLoopException($e, $message);
        }

        if ($exitCode === self::SUCCESS) {
            $this->onLoopSuccess($message);
        } else {
            $this->onLoopFail($message);
        }

        $this->onEndLoop($message);

        return $exitCode;
    }

    /**
     * @throws \SciloneToolboxBundle\PubSub\Exception\InvalidAckDeadlineTime
     * @throws LoopException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->workerTimeout = (int) $this->getOption(self::OPTION_TIMEOUT);
        $this->onStart();

        do {
            $exitCode = $this->doExecute();
        } while ($this->isTimeout() === false && $this->shouldBeStopped() === false);

        if ($this->shouldBeStopped()) {
            $this->onStoppedBySignal();
        } elseif ($this->isTimeout()) {
            $this->onStoppedByTimeout();
        }

        $this->finally($exitCode);
        $this->onEnd($exitCode);

        return self::SUCCESS;
    }

    protected function postProcess(Process $process): void
    {
        $output = $process->getOutput();
        $logLines = explode("\n", $output);
        foreach ($logLines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $log = $this->loggerFactory->createFromString($line);
            $this->logger->log(
                $log->getLevel(),
                $log->getMessage(),
                $log->getContext() + ['extra' => $log->getExtra()]
            );
        }
    }

    protected function onStoppedByTimeout(): void
    {
        //do something
    }

    protected function onStoppedBySignal(): void
    {
        //do something
    }

    protected function onEnd(int $exitCode): void
    {
        $this->logger->info(
            'Puller ended ended with peak memory usage to ' .
            $this->convertToHumanReadableSize(memory_get_peak_usage(true)),
            ['command' => $this->getName()]
        );

        $percentMemoryUsed = round(memory_get_peak_usage(true)*100/$this->getMemoryLimit());
        if ($percentMemoryUsed > static::MEMORY_USAGE_WARNING) {
            $this->logger->warning(
                'Script {command} use more than ' . static::MEMORY_USAGE_WARNING . '% of memory allowed',
                [
                    'command'             => $this->getName(),
                    'percent_memory_used' => $percentMemoryUsed,
                ]
            );
        }
    }

    protected function onStartLoop(): void
    {
        //do something
    }

    protected function onEndLoop(Message $message): void
    {
        //do something
    }

    protected function onLoopSuccess(Message $message): void
    {
        $this->subscriptionFacade->acknowledge($this->subscriptionName, $message);
    }

    protected function onLoopFail(Message $message): void
    {
        $this->subscriptionFacade->modifyAckDeadline($this->subscriptionName, $message, 0);
    }

    protected function onEmptyPull(): void
    {
        sleep(static::TIME_TO_WAIT_BETWEEN_EMPTY_MESSAGE);
    }

    /**
     * @throws LoopException
     */
    protected function onLoopException(Exception $exception, Message $message): void
    {
        throw new LoopException(
            $exception->getMessage(),
            $exception->getCode(),
            $exception->getPrevious()
        );
    }

    private function isTimeout(): bool
    {
        return (time() - $this->start) >= $this->getOption(self::OPTION_TIMEOUT);
    }

    abstract protected function getProcessCommand(Message $message): array;
}
