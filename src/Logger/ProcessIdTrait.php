<?php

namespace SciloneToolboxBundle\Logger;

trait ProcessIdTrait
{
    private ?string $processId = null;

    public function resetProcessId(): void
    {
        $this->processId = null;
    }

    public function getProcessId(): string
    {
        if ($this->processId === null) {
            $this->processId = time() . '-' . bin2hex(random_bytes(4));
        }

        return $this->processId;
    }
}
