<?php

declare(strict_types=1);

namespace Lablnet\StepWright\DTOs;

use Lablnet\StepWright\Enums\SelectorType;

class NextButtonConfig
{
    public function __construct(
        public SelectorType|string $objectType = SelectorType::Tag,
        public string $object = '',
        public ?int $wait = null
    ) {
        if (is_string($this->objectType)) {
            $this->objectType = SelectorType::tryFromString($this->objectType) ?? SelectorType::Tag;
        }
    }

    public static function fromArray(array $data): self
    {
        return new self(
            objectType: $data['object_type'] ?? $data['objectType'] ?? SelectorType::Tag,
            object: $data['object'] ?? '',
            wait: $data['wait'] ?? null
        );
    }
}
