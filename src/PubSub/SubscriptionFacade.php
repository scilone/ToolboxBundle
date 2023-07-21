<?php

namespace SciloneToolboxBundle\PubSub;

use Google\Cloud\Core\Exception\BadRequestException;
use Google\Cloud\PubSub\Message;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Subscription;
use SciloneToolboxBundle\PubSub\Exception\InvalidAckDeadlineTime;
use SciloneToolboxBundle\PubSub\Exception\UnknownSubscriptionException;

class SubscriptionFacade
{
    /**
     * @var Subscription[]
     */
    private array $subscriptions;

    public function __construct(private PubSubClient $pubSub, private array $pullOptions = [])
    {
    }

    /**
     * @throws UnknownSubscriptionException
     */
    public function getSubscription(string $name): Subscription
    {
        if (
            isset($this->subscriptions[$name]) === false
           || $this->subscriptions[$name] instanceof Subscription === false
        ) {
            $subscription = $this->pubSub->subscription($name);

            if ($subscription->exists() !== true) {
                throw new UnknownSubscriptionException();
            }

            $this->subscriptions[$name] = $subscription;
        }

        return $this->subscriptions[$name];
    }

    /**
     * @throws UnknownSubscriptionException
     */
    public function pull(string $subscriptionName, array $options = []): ?Message
    {
        $messages = $this->getSubscription($subscriptionName)->pull(
            ['maxMessages' => 1]
            + $options
            + $this->pullOptions
        );

        return $messages !== [] ? current($messages) : null;
    }

    /**
     * @throws UnknownSubscriptionException
     */
    public function pullBatch(string $subscriptionName, array $options = []): array
    {
        return $this->getSubscription($subscriptionName)->pull($options + $this->pullOptions);
    }

    /**
     * @throws UnknownSubscriptionException
     */
    public function acknowledge(string $subscriptionName, Message $message, $options = []): void
    {
        $this->getSubscription($subscriptionName)->acknowledge($message, $options);
    }

    /**
     * @throws UnknownSubscriptionException
     * @throws BadRequestException
     */
    public function acknowledgeBatch(string $subscriptionName, array $messages, $options = []): void
    {
        $this->getSubscription($subscriptionName)->acknowledgeBatch($messages, $options);
    }

    /**
     * @throws UnknownSubscriptionException
     * @throws InvalidAckDeadlineTime
     */
    public function modifyAckDeadline(string $subscriptionName, Message $message, int $seconds, array $options = []): void
    {
        if ($seconds < 0 || $seconds > 600) {
            throw new InvalidAckDeadlineTime();
        }

        $this->getSubscription($subscriptionName)->modifyAckDeadline($message, $seconds, $options);
    }
}
