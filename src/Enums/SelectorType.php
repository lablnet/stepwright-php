<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Enums;

enum SelectorType: string
{
    case Id = 'id';
    case ClassName = 'class';
    case Tag = 'tag';
    case Xpath = 'xpath';

    public static function tryFromString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }
}
