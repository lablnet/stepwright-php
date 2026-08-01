<?php

declare(strict_types=1);

namespace Lablnet\StepWright\DTOs;

class RunOptions
{
    public function __construct(
        public ?array $browser = null,
        public mixed $onResult = null
    ) {
        $this->browser = $browser ?? ['headless' => true];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            browser: $data['browser'] ?? ['headless' => true],
            onResult: $data['onResult'] ?? $data['on_result'] ?? null
        );
    }
}
