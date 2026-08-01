# Browser Options

Control browser launching, context parameters, and overall execution behaviors using `RunOptions`.

## `RunOptions` Configuration

```php
use Lablnet\StepWright\DTOs\RunOptions;

$options = new RunOptions(
    browser: [
        'headless' => true,
        'slowMo' => 50,
    ],
    context: [
        'viewport' => ['width' => 1280, 'height' => 720],
        'userAgent' => 'Mozilla/5.0 CustomBot/1.0',
    ],
    timeout: 30000,
    concurrency: 2
);
```

## Options Summary

- **`browser`**: Key-value array passed to `BrowserDriverInterface::launch()`.
- **`context`**: Key-value array passed to `BrowserDriverInterface::newContext()`.
- **`concurrency`**: Number of parallel scraper executions allowed simultaneously.
- **`timeout`**: Default timeout in milliseconds for operations.
