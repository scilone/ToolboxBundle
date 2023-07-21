<?php

namespace SciloneToolboxBundle\PubSub;

use Exception;
use Exception\InvalidAckDeadlineTime;
use Exception\UnknownSubscriptionException;
use Google\Cloud\PubSub\Message;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\SignalableCommandInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use SciloneToolboxBundle\PubSub\Exception\LoopException;
use SciloneToolboxBundle\Symfony\Command\AbstractCommand;

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
    protected const ACK_DEADLINE = 600;
    protected const TIME_TO_WAIT_BETWEEN_EMPTY_MESSAGE = 5;

    private const OPTION_TIMEOUT = 'timeout';


    protected bool $interruptConsumption = false;
    protected int $workerTimeout = 10;

    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly SubscriptionFacade $subscriptionFacade,
        protected readonly string $subscriptionName
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
     * @throws \SciloneToolboxBundle\PubSub\Exception\UnknownSubscriptionException
     * @throws LoopException
     */
    protected function doExecute(): int {
        $this->onStartLoop();

        $message = $this->subscriptionFacade->pull($this->subscriptionName);
        if ($message instanceof Message === false) {
            $this->onEmptyPull();

            return self::SUCCESS;
        }

        $this->subscriptionFacade->modifyAckDeadline(
            $this->subscriptionName,
            $message,
            static::ACK_DEADLINE
        );

        try {
            $exitCode = $this->processMessage($message);
        } catch (Exception $e) {
            $this->onLoopException($e, $message);

            return self::FAILURE;
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
     * @throws \SciloneToolboxBundle\PubSub\Exception\UnknownSubscriptionException
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

        return self::SUCCESS;
    }

    protected function onStoppedByTimeout(): void
    {
        //do something
    }

    protected function onStoppedBySignal(): void
    {
        //do something
    }

    protected function onStart(): void
    {
        //do something
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

    abstract protected function processMessage(Message $message): int;
}
