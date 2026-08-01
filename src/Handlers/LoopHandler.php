<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Handlers;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\Helpers;

class LoopHandler
{
    public static function handleForeach(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector,
        callable $executeStepFn,
        ?callable $onResult = null
    ): void {
        $count = 0;
        $itemsToLoop = [];

        if (!empty($step->object)) {
            $locAll = Helpers::locatorFor($driver, $page, $step->objectType, $step->object);
            try {
                $driver->waitForSelector($page, $step->object, ['timeout' => $step->wait ?? 5000]);
            } catch (\Throwable $e) {
                // Ignore wait timeout
            }
            $count = $driver->count($locAll);
            for ($i = 0; $i < $count; $i++) {
                $itemsToLoop[] = [$driver->nth($locAll, $i), null];
            }
            echo "   🔁 foreach found {$count} items for selector {$step->object}\n";
        } elseif (!empty($step->value) && is_string($step->value)) {
            if (preg_match('/\{\{([\w\-]+)\}\}/', $step->value, $matches)) {
                $placeholderKey = $matches[1];
                $actualData = $collector[$placeholderKey] ?? null;

                if (is_array($actualData)) {
                    foreach ($actualData as $item) {
                        $itemsToLoop[] = [null, $item];
                    }
                    $count = count($itemsToLoop);
                    echo "   🔁 foreach found {$count} items from source {$step->value}\n";
                } else {
                    echo "   ⚠️  foreach source {$step->value} (key: {$placeholderKey}) is not a list or not found\n";
                    return;
                }
            } else {
                echo "   ⚠️  foreach step.value '{$step->value}' does not contain a valid placeholder {{key}}\n";
                return;
            }
        } else {
            throw new \InvalidArgumentException("foreach step requires either 'object' (locator) or 'value' (data source)");
        }

        if (empty($step->subSteps)) {
            throw new \InvalidArgumentException("foreach step requires subSteps");
        }

        foreach ($itemsToLoop as $idx => [$currentLocator, $currentData]) {
            if ($currentLocator && $step->autoScroll !== false) {
                try {
                    $driver->scrollIntoView($currentLocator);
                } catch (\Throwable $e) {
                    // Ignore scroll error
                }
            }

            $itemCollector = [];
            foreach ($collector as $k => $v) {
                if (!preg_match('/^item_\d+$/', (string) $k)) {
                    $itemCollector[$k] = $v;
                }
            }

            $indexKey = $step->indexKey ?? 'i';

            foreach ($step->subSteps as $s) {
                $cloned = self::cloneStepWithIndex($s, $idx, $indexKey);

                if (empty($step->object)) {
                    $itemCollector['item'] = $currentData;
                    if (is_array($currentData)) {
                        $itemCollector = array_merge($itemCollector, $currentData);
                    }
                }

                try {
                    $executeStepFn($driver, $page, $cloned, $itemCollector, $onResult, $currentLocator);
                } catch (\Throwable $e) {
                    echo "⚠️  sub-step '{$cloned->id}' failed: {$e->getMessage()}\n";
                    if ($cloned->terminateOnError) {
                        throw $e;
                    }
                }
            }

            if (!empty($step->key)) {
                if (!isset($collector[$step->key]) || !is_array($collector[$step->key])) {
                    $collector[$step->key] = [];
                }

                $resultToStore = Helpers::flattenNestedForeachResults($itemCollector);

                if (is_array($resultToStore) && array_is_list($resultToStore)) {
                    $collector[$step->key] = array_merge($collector[$step->key], $resultToStore);
                } else {
                    $parentKeys = array_filter(array_keys($collector), fn($k) => !preg_match('/^item_\d+$/', (string) $k));
                    $cleanedItem = [];
                    foreach ($itemCollector as $k => $v) {
                        if (!in_array($k, $parentKeys, true) || $k === $indexKey) {
                            $cleanedItem[$k] = $v;
                        }
                    }
                    $collector[$step->key][] = $cleanedItem;
                }
            } else {
                $collector["item_{$idx}"] = $itemCollector;

                if (!empty($itemCollector)) {
                    echo "   📋 Collected data for item {$idx}: " . implode(', ', array_keys($itemCollector)) . "\n";
                    if ($onResult !== null) {
                        try {
                            $flattenedResult = Helpers::flattenNestedForeachResults($itemCollector);
                            $onResult($flattenedResult, $idx);
                        } catch (\Throwable $e) {
                            echo "   ⚠️  Callback failed for item {$idx}: {$e->getMessage()}\n";
                        }
                    }
                }
            }
        }
    }

    public static function handleOpen(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector,
        callable $executeStepFn,
        ?callable $onResult = null
    ): void {
        if (empty($step->object)) {
            throw new \InvalidArgumentException("open step requires object locator");
        }
        if (empty($step->subSteps)) {
            throw new \InvalidArgumentException("open step needs subSteps");
        }

        echo "   🔗 Opening link/tab from selector {$step->object}\n";
        try {
            $linkLoc = Helpers::locatorFor($driver, $page, $step->objectType, $step->object);
            if ($driver->count($linkLoc) === 0) {
                echo "   ⚠️  Element not found: {$step->object} - skipping open\n";
                return;
            }

            $first = $driver->first($linkLoc);
            $href = $driver->getAttribute($first, 'href');

            $newPage = $driver->newPage();

            if ($href) {
                if (!str_starts_with($href, 'http')) {
                    $currentUrl = rtrim($driver->getUrl($page), '/');
                    $href = $currentUrl . '/' . ltrim($href, '/');
                }
                $driver->goto($newPage, $href, ['waitUntil' => 'networkidle']);
            } else {
                $driver->click($first);
                $driver->waitForLoadState($newPage, 'networkidle');
            }

            $inner = $collector;
            foreach ($step->subSteps as $s) {
                $cloned = clone $s;
                try {
                    $executeStepFn($driver, $newPage, $cloned, $inner, $onResult);
                } catch (\Throwable $e) {
                    echo "   ⚠️  Sub-step in open failed: {$e->getMessage()}\n";
                    if ($cloned->terminateOnError) {
                        throw $e;
                    }
                }
            }

            $collector = array_merge($collector, $inner);
            echo "   🔙 Closed child tab\n";
            $driver->closePage($newPage);
        } catch (\Throwable $e) {
            echo "   ⚠️  Open action failed for {$step->object}: {$e->getMessage()}\n";
            if ($step->terminateOnError) {
                throw $e;
            }
        }
    }

    public static function cloneStepWithIndex(BaseStep $step, int $idx, string $char = 'i'): BaseStep
    {
        $cloned = clone $step;
        if (is_string($cloned->object)) {
            $cloned->object = Helpers::replaceIndexPlaceholders($cloned->object, $idx, $char);
        }
        if (is_string($cloned->value)) {
            $cloned->value = Helpers::replaceIndexPlaceholders($cloned->value, $idx, $char);
        }
        if (is_string($cloned->key)) {
            $cloned->key = Helpers::replaceIndexPlaceholders($cloned->key, $idx, $char);
        }
        if ($cloned->subSteps) {
            $cloned->subSteps = array_map(
                fn($s) => self::cloneStepWithIndex($s, $idx, $char),
                $cloned->subSteps
            );
        }
        return $cloned;
    }
}
