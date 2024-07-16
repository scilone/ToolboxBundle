<?php

declare(strict_types=1);

namespace SciloneToolboxBundle\Logger;

use DateTimeImmutable;

class LoggerFactory
{
    public function createFromString(string $log, bool $allowEval = false): Log
    {
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
