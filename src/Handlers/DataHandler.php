<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Handlers;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\Helpers;

class DataHandler
{
    public static function handle(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector,
        mixed $scopeLocator = null
    ): void {
        $checkSelector = $step->object ?? '';
        if ($step->dataType === 'attribute' && preg_match('/\/@\w+$/', $checkSelector)) {
            $checkSelector = preg_replace('/\/@\w+$/', '', $checkSelector);
        }

        Helpers::waitForSelectorIfConfigured($driver, $page, $step, $scopeLocator);

        [$loc, $usedType, $usedSelector] = Helpers::findLocatorWithFallbacks(
            $driver,
            $page,
            $scopeLocator,
            $step->objectType,
            $checkSelector,
            $step->fallbackSelectors
        );

        if (!$loc || $driver->count($loc) === 0) {
            $key = $step->key ?? $step->id ?? 'data';
            if ($step->required) {
                throw new \RuntimeException("Required element not found: {$checkSelector}");
            }
            $val = $step->defaultValue;
            $collector[$key] = $val;
            if ($step->continueOnEmpty === false) {
                throw new \RuntimeException("Element not found: {$checkSelector}");
            }
            echo "   ⚠️  Element not found: {$checkSelector} - using default\n";
            return;
        }

        $first = $driver->first($loc);
        $val = null;

        if ($step->dataType === 'text') {
            $val = $driver->textContent($first);
        } elseif ($step->dataType === 'html') {
            $val = $driver->innerHtml($first);
        } elseif ($step->dataType === 'value') {
            $val = $driver->inputValue($first);
        } elseif ($step->dataType === 'attribute') {
            if (preg_match('/\/@(\w+)$/', $step->object ?? '', $attrMatch)) {
                $attrName = $attrMatch[1];
                $val = $driver->getAttribute($first, $attrName);
            } else {
                $val = $driver->textContent($first);
            }
        } else {
            $val = $driver->textContent($first);
        }

        if ($val !== null) {
            $val = Helpers::transformDataRegex($val, $step->regex, $step->regexGroup);
        }

        if ($val !== null) {
            $val = Helpers::applyTransform($driver, $page, $val, $step->transform, $collector);
        }

        if ($step->required && ($val === null || $val === '')) {
            throw new \RuntimeException("Required data field is empty: " . ($step->key ?? $step->id));
        }

        if ($val === null || $val === '') {
            $val = $step->defaultValue;
        }

        if ($step->wait && $step->wait > 0) {
            $driver->waitForTimeout($page, $step->wait);
        }

        $key = $step->key ?? $step->id ?? 'data';
        $collector[$key] = $val;
        echo "Step Data: {$key}: " . (is_array($val) ? json_encode($val) : $val) . "\n";
    }
}
