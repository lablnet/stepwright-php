# Custom Drivers Guide

StepWright PHP decouples execution logic from browser engines using `Lablnet\StepWright\Contracts\BrowserDriverInterface`.

You can implement custom drivers to support alternative automation engines (such as Symfony Panther, Mink, Chrome DevTools Protocol, or mock drivers for unit testing).

---

## Implementing `BrowserDriverInterface`

To build a custom driver, implement `Lablnet\StepWright\Contracts\BrowserDriverInterface` and pass an instance of your driver class into `StepWright::runScraper()`.

```php
<?php

namespace App\Drivers;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;

class CustomBrowserDriver implements BrowserDriverInterface
{
    // Implementation of driver methods...
}
```

---

## Required Method Categories

`BrowserDriverInterface` defines 35 methods categorized by their operational responsibilities:

### 1. Browser Lifecycle Methods

Control browser session initialization and teardown:

| Method | Parameters | Return Type | Description |
| --- | --- | --- | --- |
| `launch()` | `array $options = []` | `mixed` | Initialize or launch the browser process. |
| `newContext()` | `array $options = []` | `mixed` | Create an isolated browser context/session. |
| `newPage()` | `mixed $context = null` | `mixed` | Open a new page/tab. |
| `closePage()` | `mixed $page` | `void` | Close a specific page instance. |
| `closeContext()`| `mixed $context` | `void` | Close browser context. |
| `closeBrowser()`| `mixed $browser = null` | `void` | Terminate the browser instance. |
| `shutdown()` | | `void` | Global driver cleanup. |

### 2. Navigation & Page Controls

| Method | Parameters | Return Type | Description |
| --- | --- | --- | --- |
| `goto()` | `mixed $page, string $url, array $options = []` | `void` | Navigate page to URL. |
| `reload()` | `mixed $page, array $options = []` | `void` | Reload current page. |
| `getTitle()` | `mixed $page` | `string` | Return document title. |
| `getUrl()` | `mixed $page` | `string` | Return current URL. |
| `waitForTimeout()` | `mixed $page, int $milliseconds` | `void` | Pause execution in ms. |
| `waitForLoadState()`| `mixed $page, string $state = 'load', ?int $timeout = null` | `void` | Wait for load state (`load`, `domcontentloaded`, `networkidle`). |

### 3. Locators & Element Queries

| Method | Parameters | Return Type | Description |
| --- | --- | --- | --- |
| `locator()` | `mixed $context, string $selector` | `mixed` | Construct element locator query. |
| `count()` | `mixed $locator` | `int` | Count matching elements. |
| `nth()` | `mixed $locator, int $index` | `mixed` | Get element at index `$index`. |
| `first()` | `mixed $locator` | `mixed` | Get first element. |
| `frameLocator()`| `mixed $context, string $selector` | `mixed` | Switch context to iframe. |
| `waitForSelector()`| `mixed $context, string $selector, array $options = []` | `void` | Wait for selector visibility. |

### 4. Element Actions & Data Extraction

| Method | Parameters | Return Type | Description |
| --- | --- | --- | --- |
| `click()` | `mixed $locator, array $options = []` | `void` | Single click. |
| `dblclick()` | `mixed $locator, array $options = []` | `void` | Double click. |
| `check()` | `mixed $locator, array $options = []` | `void` | Check input checkbox/radio. |
| `fill()` | `mixed $locator, string $value, array $options = []` | `void` | Set input element value. |
| `type()` | `mixed $locator, string $text, array $options = []` | `void` | Type string into element. |
| `clear()` | `mixed $locator, array $options = []` | `void` | Clear input element content. |
| `hover()` | `mixed $locator, array $options = []` | `void` | Hover mouse over element. |
| `selectOption()`| `mixed $locator, mixed $values, array $options = []` | `void` | Select option in `<select>`. |
| `dragTo()` | `mixed $sourceLocator, mixed $targetLocator, array $options = []` | `void` | Drag source element to target. |
| `setInputFiles()`| `mixed $locator, mixed $files, array $options = []` | `void` | Attach local files to file input. |
| `textContent()` | `mixed $locator` | `?string` | Return plain text content. |
| `innerText()` | `mixed $locator` | `string` | Return inner text. |
| `innerHtml()` | `mixed $locator` | `string` | Return inner HTML. |
| `inputValue()` | `mixed $locator` | `string` | Return form input value. |
| `getAttribute()`| `mixed $locator, string $name` | `?string` | Get element HTML attribute value. |
| `scrollIntoView()`| `mixed $locator` | `void` | Scroll element into view. |
| `isVisible()` | `mixed $locator` | `bool` | Check element visibility. |
| `isEnabled()` | `mixed $locator` | `bool` | Check element enabled state. |
| `evaluate()` | `mixed $context, string $expression, mixed $arg = null` | `mixed` | Execute JavaScript expression. |
| `screenshot()` | `mixed $pageOrLocator, array $options = []` | `string` | Save screenshot to disk. |

---

## Minimal Example Custom Driver

Here is an example skeleton implementation:

```php
<?php

namespace App\Drivers;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;

class ArrayMockDriver implements BrowserDriverInterface
{
    public function launch(array $options = []): mixed { return 'browser'; }
    public function newContext(array $options = []): mixed { return 'context'; }
    public function newPage(mixed $context = null): mixed { return 'page'; }
    public function closePage(mixed $page): void {}
    public function closeContext(mixed $context): void {}
    public function closeBrowser(mixed $browser = null): void {}
    public function shutdown(): void {}

    public function goto(mixed $page, string $url, array $options = []): void {}
    public function reload(mixed $page, array $options = []): void {}
    public function getTitle(mixed $page): string { return 'Mock Page'; }
    public function getUrl(mixed $page): string { return 'https://example.com'; }
    public function waitForTimeout(mixed $page, int $milliseconds): void {}
    public function waitForLoadState(mixed $page, string $state = 'load', ?int $timeout = null): void {}

    public function locator(mixed $context, string $selector): mixed { return ['selector' => $selector]; }
    public function click(mixed $locator, array $options = []): void {}
    public function dblclick(mixed $locator, array $options = []): void {}
    public function check(mixed $locator, array $options = []): void {}
    public function fill(mixed $locator, string $value, array $options = []): void {}
    public function type(mixed $locator, string $text, array $options = []): void {}
    public function clear(mixed $locator, array $options = []): void {}
    public function hover(mixed $locator, array $options = []): void {}
    public function selectOption(mixed $locator, mixed $values, array $options = []): void {}
    public function dragTo(mixed $sourceLocator, mixed $targetLocator, array $options = []): void {}
    public function setInputFiles(mixed $locator, mixed $files, array $options = []): void {}

    public function textContent(mixed $locator): ?string { return 'Sample Text'; }
    public function innerHtml(mixed $locator): string { return '<span>Sample</span>'; }
    public function innerText(mixed $locator): string { return 'Sample Text'; }
    public function inputValue(mixed $locator): string { return ''; }
    public function getAttribute(mixed $locator, string $name): ?string { return null; }
    public function count(mixed $locator): int { return 1; }
    public function nth(mixed $locator, int $index): mixed { return $locator; }
    public function first(mixed $locator): mixed { return $locator; }
    public function scrollIntoView(mixed $locator): void {}
    public function isVisible(mixed $locator): bool { return true; }
    public function isEnabled(mixed $locator): bool { return true; }
    public function evaluate(mixed $context, string $expression, mixed $arg = null): mixed { return null; }
    public function screenshot(mixed $pageOrLocator, array $options = []): string { return ''; }
    public function waitForSelector(mixed $context, string $selector, array $options = []): void {}
    public function frameLocator(mixed $context, string $selector): mixed { return null; }
}
```

Passing your custom driver to StepWright:

```php
use Lablnet\StepWright\StepWright;
use App\Drivers\CustomBrowserDriver;

$driver = new CustomBrowserDriver();
$results = StepWright::runScraper($templates, $options, $driver);
```
