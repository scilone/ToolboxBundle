<?php

namespace SciloneToolboxBundle\PubSub;

use Google\Cloud\PubSub\Message;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Subscription;
use Google\Cloud\PubSub\Topic;

class TopicFacade
{
    /**
     * @var Topic[]
     */
    private array $topics;

    public function __construct(private PubSubClient $pubSub, private array $publishOptions = [])
    {
    }

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

    public function publishMessage(string $topicName, Message $message, array $options = [], ?string $orderingKey = null): void
    {
        $topic = $this->getTopic($topicName);

        if ($orderingKey !== null) {
            $messageArray = $message->toArray();
            $messageArray['orderingKey'] = $orderingKey;
            $topic->publish($messageArray, $options + $this->publishOptions);
        } else {
            $topic->publish($message, $options + $this->publishOptions);
        }
    }

    public function publish(string $topicName, array $data, array $attributes = [], array $options = [], ?string $orderingKey = null): void
    {
        $topic = $this->getTopic($topicName);

        $message = [
            'data' => json_encode($data),
            'attributes' => ['Content-Type' => 'application/json'] + $attributes
        ];

        if ($orderingKey !== null) {
            $message['orderingKey'] = $orderingKey;
        }

        $topic->publish($message, $options + $this->publishOptions);
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
