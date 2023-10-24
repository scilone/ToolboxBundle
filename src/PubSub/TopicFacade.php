<?php

namespace SciloneToolboxBundle\PubSub;

use Google\Cloud\PubSub\Message;
use Google\Cloud\PubSub\PubSubClient;
use Google\Cloud\PubSub\Subscription;
use Google\Cloud\PubSub\Topic;
use SciloneToolboxBundle\PubSub\Exception\UnknownTopicException;

class TopicFacade
{
    /**
     * @var Topic[]
     */
    private array $topics;

    public function __construct(private PubSubClient $pubSub, private array $publishOptions = [])
    {
    }

    /**
     * @throws UnknownTopicException
     */
    public function getTopic(string $name): Topic
    {
        if (
            isset($this->topics[$name]) === false
            || $this->topics[$name] instanceof Topic === false
        ) {
            $topic = $this->pubSub->topic($name);

            if ($topic->exists() !== true) {
                throw new UnknownTopicException();
            }

            $this->topics[$name] = $topic;
        }

        return $this->topics[$name];
    }

    /**
     * @throws UnknownTopicException
     */
    public function publishMessage(string $topicName, Message $message, array $options = []): void
    {
        $topic = $this->getTopic($topicName);

        $topic->publish($message, $options + $this->publishOptions);
    }

    /**
     * @throws UnknownTopicException
     */
    public function publish(string $topicName, array $data, array $attributes = [], array $options = []): void
    {
        $topic = $this->getTopic($topicName);

        $topic->publish(
            [
                'data' => json_encode($data),
                'attributes' => ['Content-Type' => 'application/json'] + $attributes
            ],
            $options + $this->publishOptions
        );
    }

    /**
     * @param Message[] $messages
     *
     * @throws UnknownTopicException
     */
    public function publishMessageBatch(string $topicName, array $messages, array $options = []): void
    {
        $topic = $this->getTopic($topicName);

        $topic->publishBatch($messages, $options + $this->publishOptions);
    }
}
