<?php

declare(strict_types=1);

namespace Lablnet\StepWright\DTOs;

class ParallelTemplate
{
    /** @var array<TabTemplate|ParallelTemplate|ParameterizedTemplate> */
    public array $templates = [];

    public function __construct(array $templates = [])
    {
        $this->templates = $templates;
    }

    public static function fromArray(array $data): self
    {
        $templates = [];
        if (isset($data['templates']) && is_array($data['templates'])) {
            foreach ($data['templates'] as $tmpl) {
                if ($tmpl instanceof TabTemplate || $tmpl instanceof ParallelTemplate || $tmpl instanceof ParameterizedTemplate) {
                    $templates[] = $tmpl;
                } else if (is_array($tmpl)) {
                    if (isset($tmpl['template'])) {
                        $templates[] = ParameterizedTemplate::fromArray($tmpl);
                    } else if (isset($tmpl['templates'])) {
                        $templates[] = self::fromArray($tmpl);
                    } else {
                        $templates[] = TabTemplate::fromArray($tmpl);
                    }
                }
            }
        }

        return new self($templates);
    }
}
