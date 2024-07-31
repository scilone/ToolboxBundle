<?php

declare(strict_types=1);

namespace SciloneToolboxBundle\Logger;

use DateTimeImmutable;
use JsonException;

class LoggerFactory
{
    public function createFromString(string $log, bool $allowEval = false): Log
    {
        try {
            $logJsonDecoded = json_decode($log, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $logJsonDecoded = null;
        }
        if (is_array($logJsonDecoded)) {
            $datetime = null;
            if (!empty($logJsonDecoded['datetime'] ?? '')) {
                $datetime = new DateTimeImmutable($logJsonDecoded['datetime']);
            }

            return new Log(
                $logJsonDecoded['severity'] ?? 'DEBUG',
                $logJsonDecoded['message'] ?? '',
                $datetime,
                $logJsonDecoded['channel'] ?? null,
                $logJsonDecoded['context'] ?? [],
                $logJsonDecoded['extra'] ?? [],
            );
        }

        $matches = $this->normalize($log);

        $datetime = null;
        if (!empty($matches['datetime'] ?? '')) {
            $datetime = new DateTimeImmutable($matches['datetime']);
        }

        $context = [];
        if (!empty($matches['context'] ?? '')) {
            $context = json_decode($matches['context'] ?? '', true);
            if ($context === null && $allowEval) {
                $context = eval('return ' . ($matches['context'] ?? '[]') . ';');
            }
        }

        $extra = [];
        if (!empty($matches['extra'] ?? '')) {
            $extra = json_decode($matches['extra'] ?? '', true);
            if ($extra === null && $allowEval) {
                $extra = eval('return ' . ($matches['extra'] ?? '[]') . ';');
            }
        }

        return new Log(
            $matches['level'] ?? '',
            $matches['message'] ?? '',
            $datetime,
            $matches['channel'] ?? null,
            $context ?? [],
            $extra ?? [],
        );
    }

    private function normalize(string $log): array
    {
        preg_match(
            '/\[(?<datetime>.*?)\] (?<channel>.*?)\.(?<level>.*?)\: (?<message>.*?) (?<context>\{.*?\}|\[\]) (?<extra>\{.*?\}|\[\])$/',
            $log,
            $matches
        );

        if (count($matches) > 0) {
            return $matches;
        }

        preg_match(
            '/^(?<datetime>.*?) (?<level>\w+) *\[(?<channel>.*?)\] (?<message>.*?)\s*(?<context>\[.*?\])\s*(?<extra>\[.*?\])?$/',
            $log,
            $matches
        );

        return $matches;
    }
}
