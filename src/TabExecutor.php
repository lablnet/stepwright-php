<?php

declare(strict_types=1);

namespace Lablnet\StepWright;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\DTOs\PaginationConfig;
use Lablnet\StepWright\DTOs\TabTemplate;

class TabExecutor
{
    public static function executeTab(
        BrowserDriverInterface $driver,
        mixed $page,
        TabTemplate $template,
        ?callable $onResult = null
    ): array {
        $results = [];
        echo "=== TAB {$template->tab} ===\n";

        if ($template->initSteps) {
            echo "--- Running initSteps ---\n";
            $initCollected = [];
            StepExecutor::executeStepList($driver, $page, $template->initSteps, $initCollected, $onResult);
        }

        $pagination = $template->pagination;

        $runPagination = function (mixed $page, PaginationConfig $pagination, string $logPrefix = '') use ($driver): bool {
            if ($pagination->strategy === 'next' && $pagination->nextButton) {
                echo "{$logPrefix}👉 Clicking next button\n";
                try {
                    $nextLoc = Helpers::locatorFor($driver, $page, $pagination->nextButton->objectType, $pagination->nextButton->object);
                    $driver->click($driver->first($nextLoc));
                    if ($pagination->nextButton->wait) {
                        $driver->waitForTimeout($page, $pagination->nextButton->wait);
                    } else {
                        $driver->waitForLoadState($page, 'networkidle');
                    }
                    return true;
                } catch (\Throwable $e) {
                    return false;
                }
            } elseif ($pagination->strategy === 'scroll') {
                echo "{$logPrefix}🖱️  Scrolling for pagination\n";
                $offset = $pagination->scroll->offset ?? 500;
                $driver->evaluate($page, "window.scrollBy(0, {$offset})");
                $delay = $pagination->scroll->delay ?? 1000;
                $driver->waitForTimeout($page, $delay);
                return true;
            }
            return false;
        };

        if ($pagination && $pagination->paginateAllFirst) {
            $pageIndex = 0;
            while (true) {
                if ($pagination->maxPages !== null && $pageIndex >= $pagination->maxPages) {
                    break;
                }
                $paginated = $runPagination($page, $pagination, '[paginateAllFirst] ');
                if (!$paginated) {
                    break;
                }
                $pageIndex++;
            }

            $collected = [];
            $stepsForPage = (!empty($template->perPageSteps)) ? $template->perPageSteps : ($template->steps ?? []);
            StepExecutor::executeStepList($driver, $page, $stepsForPage, $collected, $onResult);

            if (!empty($stepsForPage)) {
                $itemKeys = array_filter(array_keys($collected), fn($k) => str_starts_with((string) $k, 'item_'));
                if (!empty($itemKeys)) {
                    foreach ($itemKeys as $k) {
                        $item = $collected[$k];
                        if (!empty($item)) {
                            $flattenedResult = Helpers::flattenNestedForeachResults($item);
                            if (is_array($flattenedResult) && array_is_list($flattenedResult)) {
                                $results = array_merge($results, $flattenedResult);
                            } else {
                                $results[] = $flattenedResult;
                            }
                        }
                    }
                } else {
                    $results[] = $collected;
                    if ($onResult !== null) {
                        $onResult($collected, 0);
                    }
                }
            }
            echo "=== Finished tab {$template->tab} - collected " . count($results) . " record(s) ===\n";
            return $results;
        }

        $pageIndex = 0;
        $resultIndex = 0;
        while (true) {
            echo "--- Page iteration {$pageIndex} ---\n";
            $collected = [];

            if ($pagination && $pagination->paginationFirst && $pageIndex > 0) {
                $paginated = $runPagination($page, $pagination, '[paginationFirst] ');
                if (!$paginated) {
                    break;
                }
            }

            $stepsForPage = (!empty($template->perPageSteps)) ? $template->perPageSteps : ($template->steps ?? []);
            StepExecutor::executeStepList($driver, $page, $stepsForPage, $collected, $onResult);

            if (!empty($stepsForPage)) {
                $itemKeys = array_filter(array_keys($collected), fn($k) => str_starts_with((string) $k, 'item_'));
                if (!empty($itemKeys)) {
                    foreach ($itemKeys as $k) {
                        $item = $collected[$k];
                        if (!empty($item)) {
                            $flattenedResult = Helpers::flattenNestedForeachResults($item);
                            if (is_array($flattenedResult) && array_is_list($flattenedResult)) {
                                $results = array_merge($results, $flattenedResult);
                            } else {
                                $results[] = $flattenedResult;
                            }
                            $resultIndex++;
                        }
                    }
                } else {
                    $results[] = $collected;
                    if ($onResult !== null) {
                        $onResult($collected, $resultIndex);
                    }
                    $resultIndex++;
                }
            }

            if (!$pagination) {
                echo "No pagination configured, finishing tab\n";
                break;
            }

            $pageIndex++;
            if ($pagination->maxPages !== null && $pageIndex >= $pagination->maxPages) {
                break;
            }

            if (!$pagination->paginationFirst) {
                $paginated = $runPagination($page, $pagination, '');
                if (!$paginated) {
                    break;
                }
            }
        }

        echo "=== Finished tab {$template->tab} - collected " . count($results) . " record(s) ===\n";
        return $results;
    }
}
