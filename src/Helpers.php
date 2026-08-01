<?php

declare(strict_types=1);

namespace Lablnet\StepWright;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\Enums\SelectorType;

class Helpers
{
    public static function replaceIndexPlaceholders(?string $text, int $i, string $char = 'i'): ?string
    {
        if ($text === null) {
            return null;
        }

        $text = (string) $text;
        $text = str_replace("{{ {$char} }}", (string) $i, $text);
        $text = str_replace("{{{$char}}}", (string) $i, $text);
        $text = str_replace("{{ {$char}_plus1 }}", (string) ($i + 1), $text);
        $text = str_replace("{{{$char}_plus1}}}", (string) ($i + 1), $text);

        return $text;
    }

    public static function replaceDataPlaceholders(?string $text, array $collector): ?string
    {
        if ($text === null) {
            return null;
        }

        return preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($matches) use ($collector) {
            $key = trim($matches[1]);
            $val = $collector[$key] ?? $matches[0];
            if ($val === null) {
                return $matches[0];
            }
            $s = trim((string) $val);
            $s = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $s);
            $s = preg_replace('/\s+/', '_', $s);
            return $s;
        }, (string) $text);
    }

    public static function locatorFor(BrowserDriverInterface $driver, mixed $context, SelectorType|string|null $type, string $selector): mixed
    {
        $typeStr = $type instanceof SelectorType ? $type->value : $type;

        if (empty($typeStr)) {
            return $driver->locator($context, $selector);
        }

        if ($typeStr === 'id') {
            return $driver->locator($context, "#{$selector}");
        }
        if ($typeStr === 'class') {
            return $driver->locator($context, ".{$selector}");
        }
        if ($typeStr === 'tag') {
            return $driver->locator($context, $selector);
        }
        if ($typeStr === 'xpath') {
            return $driver->locator($context, "xpath={$selector}");
        }

        return $driver->locator($context, $selector);
    }

    public static function ensureDir(string $pathStr): void
    {
        $dir = dirname($pathStr);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    public static function flattenNestedForeachResults(array $item): array
    {
        $context = [];
        foreach ($item as $k => $v) {
            if (!preg_match('/^item_\d+$/', (string) $k)) {
                $context[$k] = $v;
            }
        }

        $nestedItemKeys = array_filter(array_keys($item), fn($k) => preg_match('/^item_\d+$/', (string) $k));

        if (!empty($nestedItemKeys)) {
            usort($nestedItemKeys, fn($a, $b) => (int) explode('_', (string) $a)[1] <=> (int) explode('_', (string) $b)[1]);

            $flattenedItems = [];
            foreach ($nestedItemKeys as $k) {
                $childVal = $item[$k];
                if ($childVal !== null) {
                    if (is_array($childVal) && array_is_list($childVal)) {
                        foreach ($childVal as $subItem) {
                            if (is_array($subItem)) {
                                $flattenedItems[] = array_merge($context, $subItem);
                            } else {
                                $flattenedItems[] = $subItem;
                            }
                        }
                    } elseif (is_array($childVal)) {
                        $flattenedItems[] = array_merge($context, $childVal);
                    } else {
                        $flattenedItems[] = $childVal;
                    }
                }
            }

            return $flattenedItems;
        }

        return $item;
    }

    public static function evaluateCondition(BrowserDriverInterface $driver, mixed $page, string $condition, array $collector): bool
    {
        try {
            $conditionWithData = self::replaceDataPlaceholders($condition, $collector) ?? $condition;
            $result = $driver->evaluate($page, "() => {$conditionWithData}");
            return (bool) $result;
        } catch (\Throwable $e) {
            echo "   ⚠️  Condition evaluation failed: {$condition} - {$e->getMessage()}\n";
            return false;
        }
    }

    public static function findLocatorWithFallbacks(
        BrowserDriverInterface $driver,
        mixed $page,
        mixed $scopeLocator,
        SelectorType|string|null $objectType,
        string $objectSelector,
        ?array $fallbackSelectors
    ): array {
        $context = $scopeLocator ?? $page;

        $loc = self::locatorFor($driver, $context, $objectType, $objectSelector);
        if ($loc && $driver->count($loc) > 0) {
            return [$loc, $objectType, $objectSelector];
        }

        if ($fallbackSelectors) {
            foreach ($fallbackSelectors as $fallback) {
                $fbType = $fallback['object_type'] ?? $fallback['objectType'] ?? null;
                $fbSelector = $fallback['object'] ?? null;
                if ($fbType && $fbSelector) {
                    $fbLoc = self::locatorFor($driver, $context, $fbType, $fbSelector);
                    if ($fbLoc && $driver->count($fbLoc) > 0) {
                        echo "   ✅ Using fallback selector: {$fbType}={$fbSelector}\n";
                        return [$fbLoc, $fbType, $fbSelector];
                    }
                }
            }
        }

        return [null, null, null];
    }

    public static function applyRandomDelay(BrowserDriverInterface $driver, mixed $page, ?array $randomDelay): void
    {
        if ($randomDelay) {
            $min = $randomDelay['min'] ?? 0;
            $max = $randomDelay['max'] ?? 0;
            if ($max > $min) {
                $delay = rand($min, $max);
                $driver->waitForTimeout($page, $delay);
            }
        }
    }

    public static function transformDataRegex(mixed $value, ?string $regex, ?int $regexGroup): mixed
    {
        if (!$regex || $value === null) {
            return $value;
        }

        try {
            if (preg_match('/' . str_replace('/', '\/', $regex) . '/', (string) $value, $matches)) {
                $groupIdx = $regexGroup ?? 0;
                return $matches[$groupIdx] ?? $matches[0];
            }
        } catch (\Throwable $e) {
            echo "   ⚠️  Regex extraction failed: {$e->getMessage()}\n";
        }

        return $value;
    }

    public static function waitForSelectorIfConfigured(BrowserDriverInterface $driver, mixed $page, BaseStep $step, mixed $scopeLocator): void
    {
        if (!$step->waitForSelector) {
            return;
        }

        $waitTimeout = $step->waitForSelectorTimeout ?? 30000;
        $waitState = $step->waitForSelectorState ?? 'visible';

        try {
            $context = $scopeLocator ?? $page;
            $driver->waitForSelector($context, $step->waitForSelector, [
                'state' => $waitState,
                'timeout' => $waitTimeout,
            ]);
        } catch (\Throwable $e) {
            // Continue even if wait fails
        }
    }

    public static function applyTransform(BrowserDriverInterface $driver, mixed $page, mixed $value, ?string $transform, array $collector): mixed
    {
        if (!$transform || $value === null) {
            return $value;
        }

        try {
            $transformWithData = self::replaceDataPlaceholders($transform, $collector) ?? $transform;
            return $driver->evaluate($page, "(value) => {$transformWithData}", $value);
        } catch (\Throwable $e) {
            echo "   ⚠️  Transform failed: {$e->getMessage()}\n";
            return $value;
        }
    }
}
