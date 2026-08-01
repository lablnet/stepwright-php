<?php

declare(strict_types=1);

namespace Lablnet\StepWright;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\Handlers\DataFlowHandler;
use Lablnet\StepWright\Handlers\DataHandler;
use Lablnet\StepWright\Handlers\FileHandler;
use Lablnet\StepWright\Handlers\InteractionHandler;
use Lablnet\StepWright\Handlers\LoopHandler;
use Lablnet\StepWright\Handlers\PageActionHandler;

class StepExecutor
{
    public static function executeStep(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector,
        ?callable $onResult = null,
        mixed $scopeLocator = null
    ): void {
        echo "➡️  Step `{$step->id}` ({$step->action})\n";

        if ($step->skipIf) {
            if (Helpers::evaluateCondition($driver, $page, $step->skipIf, $collector)) {
                echo "   ⏭️  Skipping step (skipIf condition true)\n";
                return;
            }
        }

        if ($step->onlyIf) {
            if (!Helpers::evaluateCondition($driver, $page, $step->onlyIf, $collector)) {
                echo "   ⏭️  Skipping step (onlyIf condition false)\n";
                return;
            }
        }

        Helpers::applyRandomDelay($driver, $page, $step->randomDelay);

        $retryCount = $step->retry ?? 0;
        $retryDelay = $step->retryDelay ?? 1000;

        for ($attempt = 0; $attempt <= $retryCount; $attempt++) {
            try {
                self::executeStepInternal($driver, $page, $step, $collector, $onResult, $scopeLocator);
                return;
            } catch (\Throwable $e) {
                if ($attempt < $retryCount) {
                    echo "   🔄 Retry " . ($attempt + 1) . "/{$retryCount} after {$retryDelay}ms: {$e->getMessage()}\n";
                    $driver->waitForTimeout($page, $retryDelay);
                } else {
                    throw $e;
                }
            }
        }
    }

    public static function executeStepInternal(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector,
        ?callable $onResult = null,
        mixed $scopeLocator = null
    ): void {
        $currentScope = $scopeLocator;
        if ($step->frameSelector) {
            $selector = $step->frameSelector;
            if ($step->frameSelectorType === 'id') {
                $selector = "#{$selector}";
            } elseif ($step->frameSelectorType === 'class') {
                $selector = ".{$selector}";
            } elseif ($step->frameSelectorType === 'xpath') {
                $selector = "xpath={$selector}";
            }

            $currentScope = $driver->frameLocator($scopeLocator ?? $page, $selector);
        }

        try {
            $action = $step->action;

            if ($action === 'navigate') {
                $val = Helpers::replaceDataPlaceholders(is_string($step->value) ? $step->value : '', $collector) ?? '';
                Scraper::navigate($driver, $page, $val);

            } elseif ($action === 'input') {
                Helpers::waitForSelectorIfConfigured($driver, $page, $step, $currentScope);
                [$loc, $usedType, $usedSelector] = Helpers::findLocatorWithFallbacks(
                    $driver, $page, $currentScope, $step->objectType, $step->object ?? '', $step->fallbackSelectors
                );

                if (!$loc || $driver->count($loc) === 0) {
                    if ($step->continueOnEmpty === false) {
                        throw new \RuntimeException("Input element not found: {$step->object}");
                    }
                    echo "   ⚠️  Input element not found: {$step->object}\n";
                    return;
                }

                $first = $driver->first($loc);

                if ($step->clearBeforeInput !== false) {
                    $driver->clear($first);
                }

                $value = Helpers::replaceDataPlaceholders(is_string($step->value) ? $step->value : '', $collector) ?? (string) $step->value;
                if ($step->inputDelay && $step->inputDelay > 0) {
                    $driver->type($first, $value, ['delay' => $step->inputDelay]);
                } else {
                    $driver->fill($first, $value);
                }

                if ($step->wait && $step->wait > 0) {
                    $driver->waitForTimeout($page, $step->wait);
                }

            } elseif ($action === 'click') {
                Helpers::waitForSelectorIfConfigured($driver, $page, $step, $currentScope);
                [$loc, $usedType, $usedSelector] = Helpers::findLocatorWithFallbacks(
                    $driver, $page, $currentScope, $step->objectType, $step->object ?? '', $step->fallbackSelectors
                );

                if (!$loc || $driver->count($loc) === 0) {
                    if ($step->continueOnEmpty === false) {
                        throw new \RuntimeException("Element not found: {$step->object}");
                    }
                    echo "   ⚠️  Element not found: {$step->object} - skipping click\n";
                    return;
                }

                $first = $driver->first($loc);

                if ($step->requireVisible !== false) {
                    if (!$driver->isVisible($first)) {
                        if ($step->forceClick) {
                            echo "   ⚠️  Element not visible, using force click\n";
                        } else {
                            throw new \RuntimeException("Element not visible: {$usedSelector}");
                        }
                    }
                }

                if ($step->requireEnabled) {
                    if (!$driver->isEnabled($first)) {
                        throw new \RuntimeException("Element not enabled: {$usedSelector}");
                    }
                }

                try {
                    $modifiers = $step->clickModifiers ?? [];
                    if ($step->doubleClick) {
                        $driver->dblclick($first, ['modifiers' => $modifiers]);
                    } else {
                        $driver->click($first, ['modifiers' => $modifiers, 'force' => (bool) $step->forceClick]);
                    }
                } catch (\Throwable $e) {
                    if ($step->skipOnError) {
                        echo "   ⚠️  Click failed (skipping): {$e->getMessage()}\n";
                    } else {
                        throw $e;
                    }
                }

            } elseif ($action === 'data') {
                try {
                    DataHandler::handle($driver, $page, $step, $collector, $currentScope);
                } catch (\Throwable $e) {
                    echo "   ⚠️  Data extraction failed for {$step->object}: {$e->getMessage()}\n";
                    $key = $step->key ?? $step->id ?? 'data';
                    if ($step->required) {
                        throw $e;
                    }
                    $collector[$key] = $step->defaultValue;
                }

            } elseif ($action === 'eventBaseDownload') {
                FileHandler::handleEventDownload($driver, $page, $step, $collector);

            } elseif ($action === 'foreach') {
                LoopHandler::handleForeach($driver, $page, $step, $collector, [self::class, 'executeStep'], $onResult);

            } elseif ($action === 'open') {
                LoopHandler::handleOpen($driver, $page, $step, $collector, [self::class, 'executeStep'], $onResult);

            } elseif ($action === 'scroll') {
                $offset = is_numeric($step->value) ? (int) $step->value : 500;
                $driver->evaluate($page, "window.scrollBy(0, {$offset})");

            } elseif ($action === 'savePDF') {
                FileHandler::handleSavePDF($driver, $page, $step, $collector);

            } elseif (in_array($action, ['downloadPDF', 'downloadFile'], true)) {
                FileHandler::handleDownloadPDF($driver, $page, $step, $collector);

            } elseif ($action === 'reload') {
                PageActionHandler::handleReload($driver, $page, $step, $collector);

            } elseif ($action === 'getUrl') {
                PageActionHandler::handleGetUrl($driver, $page, $step, $collector);

            } elseif ($action === 'getTitle') {
                PageActionHandler::handleGetTitle($driver, $page, $step, $collector);

            } elseif ($action === 'getMeta') {
                PageActionHandler::handleGetMeta($driver, $page, $step, $collector);

            } elseif ($action === 'getCookies') {
                PageActionHandler::handleGetCookies($driver, $page, $step, $collector);

            } elseif ($action === 'setCookies') {
                PageActionHandler::handleSetCookies($driver, $page, $step, $collector);

            } elseif ($action === 'getLocalStorage') {
                PageActionHandler::handleGetLocalStorage($driver, $page, $step, $collector);

            } elseif ($action === 'setLocalStorage') {
                PageActionHandler::handleSetLocalStorage($driver, $page, $step, $collector);

            } elseif ($action === 'getSessionStorage') {
                PageActionHandler::handleGetSessionStorage($driver, $page, $step, $collector);

            } elseif ($action === 'setSessionStorage') {
                PageActionHandler::handleSetSessionStorage($driver, $page, $step, $collector);

            } elseif ($action === 'getViewportSize') {
                PageActionHandler::handleGetViewportSize($driver, $page, $step, $collector);

            } elseif ($action === 'setViewportSize') {
                PageActionHandler::handleSetViewportSize($driver, $page, $step, $collector);

            } elseif ($action === 'screenshot') {
                PageActionHandler::handleScreenshot($driver, $page, $step, $collector);

            } elseif ($action === 'waitForSelector') {
                PageActionHandler::handleWaitForSelector($driver, $page, $step, $collector);

            } elseif ($action === 'evaluate') {
                PageActionHandler::handleEvaluate($driver, $page, $step, $collector);

            } elseif ($action === 'hover') {
                InteractionHandler::handleHover($driver, $page, $step, $collector, $currentScope);

            } elseif ($action === 'select') {
                InteractionHandler::handleSelect($driver, $page, $step, $collector, $currentScope);

            } elseif ($action === 'dragAndDrop') {
                InteractionHandler::handleDragAndDrop($driver, $page, $step, $collector, $currentScope);

            } elseif ($action === 'uploadFile') {
                InteractionHandler::handleUpload($driver, $page, $step, $collector, $currentScope);

            } elseif ($action === 'virtualScroll') {
                InteractionHandler::handleVirtualScroll(
                    $driver, $page, $step, $collector, [self::class, 'executeStepInternal'], $onResult, $currentScope
                );

            } elseif ($action === 'readData') {
                DataFlowHandler::handleReadData($driver, $page, $step, $collector);

            } elseif ($action === 'writeData') {
                DataFlowHandler::handleWriteData($driver, $page, $step, $collector);

            } elseif ($action === 'custom') {
                DataFlowHandler::handleCustomCallback($driver, $page, $step, $collector);
            }

            if ($step->wait && $step->wait > 0) {
                $driver->waitForTimeout($page, $step->wait);
            }

        } catch (\Throwable $e) {
            if ($step->skipOnError) {
                echo "   ⚠️  Step '{$step->id}' error (skipping): {$e->getMessage()}\n";
                return;
            }
            if ($step->terminateOnError) {
                throw $e;
            }
            echo "   ⚠️  Step '{$step->id}' error (ignored): {$e->getMessage()}\n";
        }
    }

    public static function executeStepList(
        BrowserDriverInterface $driver,
        mixed $page,
        array $steps,
        array &$collected,
        ?callable $onResult = null
    ): void {
        echo "📝 Executing " . count($steps) . " step(s)\n";
        foreach ($steps as $step) {
            try {
                self::executeStep($driver, $page, $step, $collected, $onResult);
            } catch (\Throwable $e) {
                if ($step->terminateOnError) {
                    throw $e;
                }
            }
        }
    }
}
