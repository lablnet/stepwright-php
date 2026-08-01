<?php

declare(strict_types=1);

namespace Lablnet\StepWright\DTOs;

class ScrollConfig
{
    public function __construct(
        public ?int $offset = null,
        public ?int $delay = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            offset: $data['offset'] ?? null,
            delay: $data['delay'] ?? null
        );
    }
}
