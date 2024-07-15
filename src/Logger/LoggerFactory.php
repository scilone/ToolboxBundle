<?php

declare(strict_types=1);

namespace SciloneToolboxBundle\Logger;

use DateTimeImmutable;

class LoggerFactory
{
    public function createFromString(string $log): Log
    {
        preg_match(
            '/\[(?<datetime>.*?)\] (?<channel>.*?)\.(?<level>.*?)\: (?<message>.*?) (?<context>\{.*?\}|\[\]) (?<extra>\{.*?\}|\[\])$/',
            $log,
            $matches
        );

        $datetime = null;
        if (!empty($matches['datetime'] ?? '')) {
            $datetime = new DateTimeImmutable($matches['datetime']);
        }

        $context = null;
        if (!empty($matches['context'] ?? '')) {
            $context = json_decode($matches['context'] ?? '', true);
        }

        $extra = null;
        if (!empty($matches['extra'] ?? '')) {
            $extra = json_decode($matches['extra'] ?? '', true);
        }

        return new Log(
            $matches['level'] ?? '',
            $matches['message'] ?? '',
            $datetime,
            $matches['channel'] ?? null,
            $context,
            $extra,
        );
    }
}
