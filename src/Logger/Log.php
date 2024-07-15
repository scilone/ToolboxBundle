<?php

declare(strict_types=1);

namespace SciloneToolboxBundle\Logger;

use DateTimeImmutable;

readonly class Log
{
    public function __construct(
        private string $level,
        private string $message,
        private ?DateTimeImmutable $dateTime = null,
        private ?string $channel = null,
        private ?array $context = null,
        private ?array $extra = null,
    ) {}

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDateTime(): ?DateTimeImmutable
    {
        return $this->dateTime;
    }

    public function getChannel(): ?string
    {
        return $this->channel;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }
}
