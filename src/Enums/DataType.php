<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Enums;

enum DataType: string
{
    case Text = 'text';
    case Html = 'html';
    case Value = 'value';
    case Default = 'default';
    case Attribute = 'attribute';
    case Custom = 'custom';
    case Full = 'full';
    case Json = 'json';
    case Csv = 'csv';
    case Excel = 'excel';

    public static function tryFromString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }
}
