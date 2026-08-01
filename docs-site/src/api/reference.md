# API Reference

Core classes, DTOs, and interfaces in `Lablnet\StepWright`.

## Orchestrator Class

### `Lablnet\StepWright\StepWright`

The main entry point for running scraping operations.

```php
public static function runScraper(
    array $templates,
    RunOptions $options = new RunOptions(),
    ?BrowserDriverInterface $driver = null,
    ?callable $onResult = null,
    ?callable $onStatus = null
): array
```

---

## Data Transfer Objects (DTOs)

### `Lablnet\StepWright\DTOs\BaseStep`

Represents an individual step in a template workflow.

| Property | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `string` | required | Unique identifier for the step |
| `action` | `ActionType\|string` | required | Action to perform (e.g. `navigate`, `click`, `data`, `loop`) |
| `objectType` | `SelectorType\|string` | `SelectorType::Tag` | Selector type (`css`, `xpath`, `id`, `text`, etc.) |
| `object` | `mixed` | `null` | Target element selector or payload |
| `value` | `mixed` | `null` | Value to fill/type or option argument |
| `key` | `?string` | `null` | Key name for storing extracted data in results |
| `wait` | `?int` | `null` | Timeout wait in milliseconds after step execution |
| `ignoreError` | `bool` | `false` | Whether to ignore errors during step execution |
| `terminateOnError` | `bool` | `false` | Halt workflow execution immediately if step fails |
| `subSteps` | `?array` | `null` | Nested child steps (used by `loop` action) |
| `callback` | `?callable` | `null` | Custom PHP closure executed when action is `custom` |

---

### `Lablnet\StepWright\DTOs\TabTemplate`

Represents execution logic for a single browser tab.

```php
new TabTemplate(
    string $tab,
    array $steps,
    ?PaginationConfig $pagination = null
)
```

---

### `Lablnet\StepWright\DTOs\PaginationConfig`

```php
new PaginationConfig(
    ?NextButtonConfig $nextButton = null,
    ?int $maxPages = null,
    bool $paginationFirst = false,
    bool $scrollBefore = false,
    bool $scrollAfter = false,
    ?int $scrollTimeout = null
)
```

---

### `Lablnet\StepWright\DTOs\NextButtonConfig`

```php
new NextButtonConfig(
    SelectorType|string $objectType = SelectorType::Tag,
    string $object = '',
    ?int $wait = null
)
```
