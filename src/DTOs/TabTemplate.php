<?php

declare(strict_types=1);

namespace Lablnet\StepWright\DTOs;

class TabTemplate
{
    /** @var BaseStep[]|null */
    public ?array $initSteps = null;

    /** @var BaseStep[]|null */
    public ?array $perPageSteps = null;

    /** @var BaseStep[]|null */
    public ?array $steps = null;

    public function __construct(
        public string $tab,
        ?array $initSteps = null,
        ?array $perPageSteps = null,
        ?array $steps = null,
        public ?PaginationConfig $pagination = null
    ) {
        $this->initSteps = $initSteps;
        $this->perPageSteps = $perPageSteps;
        $this->steps = $steps;
    }

    public static function fromArray(array $data): self
    {
        $mapSteps = fn($steps) => is_array($steps)
            ? array_map(fn($s) => $s instanceof BaseStep ? $s : BaseStep::fromArray($s), $steps)
            : null;

        $pagination = null;
        if (isset($data['pagination']) && is_array($data['pagination'])) {
            $pagination = PaginationConfig::fromArray($data['pagination']);
        }

        return new self(
            tab: (string) ($data['tab'] ?? 'default'),
            initSteps: $mapSteps($data['initSteps'] ?? null),
            perPageSteps: $mapSteps($data['perPageSteps'] ?? null),
            steps: $mapSteps($data['steps'] ?? null),
            pagination: $pagination
        );
    }
}
