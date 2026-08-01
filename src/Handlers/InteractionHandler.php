<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Handlers;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\Helpers;

class InteractionHandler
{
    public static function handleHover(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector,
        mixed $scopeLocator = null
    ): void {
        Helpers::waitForSelectorIfConfigured($driver, $page, $step, $scopeLocator);
        [$loc, $type, $selector] = Helpers::findLocatorWithFallbacks(
            $driver, $page, $scopeLocator, $step->objectType, $step->object ?? '', $step->fallbackSelectors
        );
        if ($loc && $driver->count($loc) > 0) {
            $driver->hover($driver->first($loc));
            echo "   🖱️  Hovered over: {$selector}\n";
        } else {
            echo "   ⚠️  Hover element not found: {$step->object}\n";
        }
    }

    public static function handleSelect(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector,
        mixed $scopeLocator = null
    ): void {
        Helpers::waitForSelectorIfConfigured($driver, $page, $step, $scopeLocator);
        [$loc, $type, $selector] = Helpers::findLocatorWithFallbacks(
            $driver, $page, $scopeLocator, $step->objectType, $step->object ?? '', $step->fallbackSelectors
        );
        if ($loc && $driver->count($loc) > 0) {
            $value = Helpers::replaceDataPlaceholders(is_string($step->value) ? $step->value : null, $collector) ?? $step->value;
            $finalValue = $value;
            if (is_string($value) && str_contains($value, ',')) {
                $finalValue = array_map('trim', explode(',', $value));
            }
            $driver->selectOption($driver->first($loc), $finalValue);
            echo "   ✅ Selected options for: {$selector}\n";
        } else {
            echo "   ⚠️  Select element not found: {$step->object}\n";
        }
    }

    public static function handleDragAndDrop(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector,
        mixed $scopeLocator = null
    ): void {
        Helpers::waitForSelectorIfConfigured($driver, $page, $step, $scopeLocator);

        [$srcLoc, $srcType, $srcSelector] = Helpers::findLocatorWithFallbacks(
            $driver, $page, $scopeLocator, $step->objectType, $step->object ?? '', $step->fallbackSelectors
        );
        [$targetLoc, $targetType, $targetSelector] = Helpers::findLocatorWithFallbacks(
            $driver, $page, $scopeLocator, $step->targetObjectType, $step->targetObject ?? '', null
        );

        if ($srcLoc && $targetLoc && $driver->count($srcLoc) > 0 && $driver->count($targetLoc) > 0) {
            $driver->hover($driver->first($srcLoc));
            $driver->dragTo($driver->first($srcLoc), $driver->first($targetLoc));
            $driver->waitForTimeout($page, 1000);
            echo "   🤝 Dragged {$srcSelector} to {$targetSelector}\n";
        } else {
            echo "   ⚠️  Drag/Drop elements not found: {$step->object} -> {$step->targetObject}\n";
        }
    }

    public static function handleUpload(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector,
        mixed $scopeLocator = null
    ): void {
        Helpers::waitForSelectorIfConfigured($driver, $page, $step, $scopeLocator);
        [$loc, $type, $selector] = Helpers::findLocatorWithFallbacks(
            $driver, $page, $scopeLocator, $step->objectType, $step->object ?? '', $step->fallbackSelectors
        );
        if ($loc && $driver->count($loc) > 0) {
            $filePath = Helpers::replaceDataPlaceholders(is_string($step->value) ? $step->value : null, $collector) ?? $step->value;
            if ($filePath) {
                $driver->setInputFiles($driver->first($loc), $filePath);
                echo "   📤 Uploaded file to: {$selector} -> {$filePath}\n";
            }
        } else {
            echo "   ⚠️  Upload element not found: {$step->object}\n";
        }
    }

    public static function handleVirtualScroll(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector,
        callable $executeStepInternalFn,
        ?callable $onResult = null,
        mixed $scopeLocator = null
    ): void {
        echo "   🌀 Starting virtual scroll for: {$step->object}\n";

        $uniqueKey = $step->virtualScrollUniqueKey;
        $limit = $step->virtualScrollLimit ?? 1000;
        $offset = $step->virtualScrollOffset ?? 500;
        $delay = $step->virtualScrollDelay ?? 1000;

        $seenIds = [];
        $totalCollected = 0;
        $noNewItemCount = 0;
        $maxNoNewItems = 3;

        $key = $step->key ?? $step->id ?? 'items';
        if (!isset($collector[$key]) || !is_array($collector[$key])) {
            $collector[$key] = [];
        }

        while ($totalCollected < $limit) {
            [$loc, $type, $selector] = Helpers::findLocatorWithFallbacks(
                $driver, $page, $scopeLocator, $step->objectType, $step->object ?? '', $step->fallbackSelectors
            );

            $count = $loc ? $driver->count($loc) : 0;
            $newItemsThisRound = 0;

            for ($i = 0; $i < $count; $i++) {
                $itemLoc = $driver->nth($loc, $i);
                $itemData = [];

                if ($step->subSteps) {
                    foreach ($step->subSteps as $sub) {
                        $executeStepInternalFn($driver, $page, $sub, $itemData, null, $itemLoc);
                    }
                }

                $itemId = $uniqueKey && isset($itemData[$uniqueKey])
                    ? (string) $itemData[$uniqueKey]
                    : json_encode($itemData);

                if (!isset($seenIds[$itemId])) {
                    $seenIds[$itemId] = true;
                    $collector[$key][] = $itemData;
                    $totalCollected++;
                    $newItemsThisRound++;

                    if ($onResult !== null) {
                        $onResult($itemData, $totalCollected - 1);
                    }

                    if ($totalCollected >= $limit) {
                        break;
                    }
                }
            }

            if ($newItemsThisRound === 0) {
                $noNewItemCount++;
            } else {
                $noNewItemCount = 0;
            }

            if ($noNewItemCount >= $maxNoNewItems) {
                echo "   🛑 No new items found after {$maxNoNewItems} scrolls, finishing.\n";
                break;
            }

            if ($step->virtualScrollContainer) {
                [$containerLoc] = Helpers::findLocatorWithFallbacks(
                    $driver, $page, $scopeLocator, $step->virtualScrollContainerType, $step->virtualScrollContainer, null
                );
                if ($containerLoc && $driver->count($containerLoc) > 0) {
                    $driver->evaluate($driver->first($containerLoc), "el => el.scrollBy(0, {$offset})");
                } else {
                    echo "   ⚠️  Virtual scroll container not found: {$step->virtualScrollContainer}\n";
                    break;
                }
            } else {
                $driver->evaluate($page, "window.scrollBy(0, {$offset})");
            }

            $driver->waitForTimeout($page, $delay);
        }

        echo "   ✅ Virtual scroll finished. Collected {$totalCollected} items.\n";
    }
}
