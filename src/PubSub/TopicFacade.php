<?php

namespace SciloneToolboxBundle\PubSub;

use Google\Cloud\PubSub\Message;
use Google\Cloud\PubSub\MessageBuilder;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Subscription;
use Google\Cloud\PubSub\Topic;

class TopicFacade
{
    /**
     * @var Topic[]
     */
    private array $topics;

    public function __construct(private readonly PubSubClient $pubSub, private readonly array $publishOptions = []) {}

    public function getTopic(string $name): Topic
    {
        if (
            isset($this->topics[$name]) === false
            || $this->topics[$name] instanceof Topic === false
        ) {
            $topic = $this->pubSub->topic($name);

            $this->topics[$name] = $topic;
        }

        return $this->topics[$name];
    }

    public function publishMessage(
        string $topicName,
        Message $message,
        array $options = []
    ): void {
        $topic = $this->getTopic($topicName);

        $topic->publish($message, $options + $this->publishOptions);
    }

    public function publish(
        string $topicName,
        array $data,
        array $attributes = [],
        array $options = [],
        ?string $orderingKey = null
    ): void {
        $topic = $this->getTopic($topicName);

        $message = $this->buildMessage($data, $attributes, $orderingKey);

        if ($orderingKey !== null) {
            $options['enableMessageOrdering'] = true;
        }

        $topic->publish(
            $message,
            $options + $this->publishOptions
        );
    }

    public function buildMessage(
        array $data,
        array $attributes = [],
        ?string $orderingKey = null
    ): Message {
        $messageBuilder = (new MessageBuilder())
            ->setData(json_encode($data))
            ->setAttributes(['Content-Type' => 'application/json'] + $attributes);

        if ($orderingKey !== null) {
            $messageBuilder->setOrderingKey($orderingKey);
        }

        return $messageBuilder->build();
    }

    /**
     * @param Message[] $messages
     */
    public function publishMessageBatch(string $topicName, array $messages, array $options = []): void
    {
        $topic = $this->getTopic($topicName);

        $topic->publishBatch($messages, $options + $this->publishOptions);
    }
}
