<?php

declare(strict_types=1);

namespace Lablnet\StepWright\DTOs;

class PaginationConfig
{
    public function __construct(
        public string $strategy = 'next', // 'next' or 'scroll'
        public ?NextButtonConfig $nextButton = null,
        public ?ScrollConfig $scroll = null,
        public ?int $maxPages = null,
        public ?bool $paginationFirst = null,
        public ?bool $paginateAllFirst = null
    ) {}

    public static function fromArray(array $data): self
    {
        $nextButton = null;
        if (isset($data['nextButton']) && is_array($data['nextButton'])) {
            $nextButton = NextButtonConfig::fromArray($data['nextButton']);
        }

        $scroll = null;
        if (isset($data['scroll']) && is_array($data['scroll'])) {
            $scroll = ScrollConfig::fromArray($data['scroll']);
        }

        return new self(
            strategy: $data['strategy'] ?? 'next',
            nextButton: $nextButton,
            scroll: $scroll,
            maxPages: $data['maxPages'] ?? null,
            paginationFirst: $data['paginationFirst'] ?? null,
            paginateAllFirst: $data['paginateAllFirst'] ?? null
        );
    }
}
