<?php

declare(strict_types=1);

namespace Lablnet\StepWright;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\DTOs\ParallelTemplate;
use Lablnet\StepWright\DTOs\ParameterizedTemplate;
use Lablnet\StepWright\DTOs\RunOptions;
use Lablnet\StepWright\DTOs\TabTemplate;

class StepWright
{
    public static function runScraper(
        array $templates,
        ?RunOptions $options = null,
        ?BrowserDriverInterface $driver = null
    ): array {
        $options = $options ?? new RunOptions();
        $driver = $driver ?? Scraper::getDefaultDriver();

        $browser = $driver->launch($options->browser ?? ['headless' => true]);
        $context = $driver->newContext();

        $allResults = [];

        $processTemplate = function (mixed $tmpl, mixed $currentContext) use (&$processTemplate, $driver, $options): array {
            $results = [];

            if ($tmpl instanceof TabTemplate) {
                $page = $driver->newPage($currentContext);
                try {
                    $tabResults = TabExecutor::executeTab($driver, $page, $tmpl, $options->onResult);
                    $results = array_merge($results, $tabResults);
                } finally {
                    $driver->closePage($page);
                }
            } elseif ($tmpl instanceof ParallelTemplate) {
                foreach ($tmpl->templates as $subTmpl) {
                    $subResults = $processTemplate($subTmpl, $currentContext);
                    $results = array_merge($results, $subResults);
                }
            } elseif ($tmpl instanceof ParameterizedTemplate) {
                foreach ($tmpl->values as $val) {
                    $clonedTmpl = clone $tmpl->template;
                    $placeholder = "{{{$tmpl->parameterKey}}}";

                    if (str_contains($clonedTmpl->tab, $placeholder)) {
                        $clonedTmpl->tab = str_replace($placeholder, (string) $val, $clonedTmpl->tab);
                    } else {
                        $clonedTmpl->tab = "{$clonedTmpl->tab}_{$val}";
                    }

                    $injectParam = function (?array $steps, string $key, mixed $value) use (&$injectParam) {
                        if (!$steps) return null;
                        $placeholder = "{{{$key}}}";
                        $updated = [];
                        foreach ($steps as $s) {
                            $stepClone = clone $s;
                            if (is_string($stepClone->value) && str_contains($stepClone->value, $placeholder)) {
                                $stepClone->value = str_replace($placeholder, (string) $value, $stepClone->value);
                            }
                            if (is_string($stepClone->object) && str_contains($stepClone->object, $placeholder)) {
                                $stepClone->object = str_replace($placeholder, (string) $value, $stepClone->object);
                            }
                            if ($stepClone->subSteps) {
                                $stepClone->subSteps = $injectParam($stepClone->subSteps, $key, $value);
                            }
                            $updated[] = $stepClone;
                        }
                        return $updated;
                    };

                    if ($clonedTmpl->steps) {
                        $clonedTmpl->steps = $injectParam($clonedTmpl->steps, $tmpl->parameterKey, $val);
                    }
                    if ($clonedTmpl->initSteps) {
                        $clonedTmpl->initSteps = $injectParam($clonedTmpl->initSteps, $tmpl->parameterKey, $val);
                    }
                    if ($clonedTmpl->perPageSteps) {
                        $clonedTmpl->perPageSteps = $injectParam($clonedTmpl->perPageSteps, $tmpl->parameterKey, $val);
                    }

                    $paramResults = $processTemplate($clonedTmpl, $currentContext);
                    $results = array_merge($results, $paramResults);
                }
            }

            return $results;
        };

        try {
            foreach ($templates as $tmpl) {
                $tmplObj = $tmpl;
                if (is_array($tmpl)) {
                    if (isset($tmpl['template'])) {
                        $tmplObj = ParameterizedTemplate::fromArray($tmpl);
                    } elseif (isset($tmpl['templates'])) {
                        $tmplObj = ParallelTemplate::fromArray($tmpl);
                    } else {
                        $tmplObj = TabTemplate::fromArray($tmpl);
                    }
                }
                $finalResults = $processTemplate($tmplObj, $context);
                $allResults = array_merge($allResults, $finalResults);
            }
        } finally {
            $driver->closeContext($context);
            $driver->closeBrowser($browser);
            $driver->shutdown();
        }

        return $allResults;
    }

    public static function runScraperWithCallback(
        array $templates,
        callable $onResult,
        ?RunOptions $options = null,
        ?BrowserDriverInterface $driver = null
    ): void {
        $options = $options ?? new RunOptions();
        $options->onResult = $onResult;
        self::runScraper($templates, $options, $driver);
    }
}
