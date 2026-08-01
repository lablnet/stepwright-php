<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Enums;

enum ClickModifier: string
{
    case Control = 'Control';
    case Meta = 'Meta';
    case Shift = 'Shift';
    case Alt = 'Alt';

    public static function tryFromString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }
}
