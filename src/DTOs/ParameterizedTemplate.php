<?php

declare(strict_types=1);

namespace Lablnet\StepWright\DTOs;

class ParameterizedTemplate
{
    public function __construct(
        public TabTemplate $template,
        public string $parameterKey,
        public array $values = []
    ) {}

    public static function fromArray(array $data): self
    {
        $tmpl = $data['template'] ?? [];
        $templateObj = $tmpl instanceof TabTemplate ? $tmpl : TabTemplate::fromArray($tmpl);

        return new self(
            template: $templateObj,
            parameterKey: (string) ($data['parameter_key'] ?? $data['parameterKey'] ?? 'param'),
            values: (array) ($data['values'] ?? [])
        );
    }
}
