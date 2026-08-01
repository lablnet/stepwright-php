<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Contracts;

interface BrowserDriverInterface
{
    /** Launch or initialize the browser instance */
    public function launch(array $options = []): mixed;

    /** Create a new context */
    public function newContext(array $options = []): mixed;

    /** Create a new page */
    public function newPage(mixed $context = null): mixed;

    /** Close page */
    public function closePage(mixed $page): void;

    /** Close context */
    public function closeContext(mixed $context): void;

    /** Close browser */
    public function closeBrowser(mixed $browser = null): void;

    /** Shutdown driver singleton */
    public function shutdown(): void;

    /** Navigate page to URL */
    public function goto(mixed $page, string $url, array $options = []): void;

    /** Reload page */
    public function reload(mixed $page, array $options = []): void;

    /** Get page title */
    public function getTitle(mixed $page): string;

    /** Get page current URL */
    public function getUrl(mixed $page): string;

    /** Wait for timeout in ms */
    public function waitForTimeout(mixed $page, int $milliseconds): void;

    /** Wait for load state */
    public function waitForLoadState(mixed $page, string $state = 'load', ?int $timeout = null): void;

    /** Create locator */
    public function locator(mixed $context, string $selector): mixed;

    /** Click on element/locator */
    public function click(mixed $locator, array $options = []): void;

    /** Double click */
    public function dblclick(mixed $locator, array $options = []): void;

    /** Check checkbox */
    public function check(mixed $locator, array $options = []): void;

    /** Fill input field */
    public function fill(mixed $locator, string $value, array $options = []): void;

    /** Type into input field character by character */
    public function type(mixed $locator, string $text, array $options = []): void;

    /** Clear input field */
    public function clear(mixed $locator, array $options = []): void;

    /** Hover element */
    public function hover(mixed $locator, array $options = []): void;

    /** Select option */
    public function selectOption(mixed $locator, mixed $values, array $options = []): void;

    /** Drag to target locator */
    public function dragTo(mixed $sourceLocator, mixed $targetLocator, array $options = []): void;

    /** Set input file paths */
    public function setInputFiles(mixed $locator, mixed $files, array $options = []): void;

    /** Get text content */
    public function textContent(mixed $locator): ?string;

    /** Get inner HTML */
    public function innerHtml(mixed $locator): string;

    /** Get inner text */
    public function innerText(mixed $locator): string;

    /** Get input value */
    public function inputValue(mixed $locator): string;

    /** Get attribute value */
    public function getAttribute(mixed $locator, string $name): ?string;

    /** Count matching elements */
    public function count(mixed $locator): int;

    /** Get nth element of locator */
    public function nth(mixed $locator, int $index): mixed;

    /** Get first element of locator */
    public function first(mixed $locator): mixed;

    /** Scroll element into view */
    public function scrollIntoView(mixed $locator): void;

    /** Check if visible */
    public function isVisible(mixed $locator): bool;

    /** Check if enabled */
    public function isEnabled(mixed $locator): bool;

    /** Evaluate JavaScript expression */
    public function evaluate(mixed $context, string $expression, mixed $arg = null): mixed;

    /** Take screenshot */
    public function screenshot(mixed $pageOrLocator, array $options = []): string;

    /** Wait for selector */
    public function waitForSelector(mixed $context, string $selector, array $options = []): void;

    /** Handle frame locator */
    public function frameLocator(mixed $context, string $selector): mixed;
}
