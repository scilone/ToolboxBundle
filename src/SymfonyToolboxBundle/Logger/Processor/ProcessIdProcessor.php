<?php

namespace SymfonyToolboxBundle\SymfonyToolboxBundle\Logger\Processor;

use Monolog\Processor\ProcessorInterface;
use Monolog\ResettableInterface;

class ProcessIdProcessor implements ProcessorInterface, ResettableInterface
{
    private ?string $processId = null;

    public function __invoke(array $record): array
    {
        $record['extra']['process_id'] = $this->getProcessId();

        return $record;
    }

    public function reset(): void
    {
        $this->processId = null;
    }

    private function getProcessId(): string
    {
        if ($this->processId === null) {
            $this->processId = time() . '-' . bin2hex(random_bytes(4));
        }

        return $this->processId;
    }
}
