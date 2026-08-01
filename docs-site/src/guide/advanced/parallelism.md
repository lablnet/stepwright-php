# Parallelism & Templates

StepWright PHP supports multiple template abstractions to run complex multi-tab and parameterised workflows.

## `ParallelTemplate`

Run multiple templates in parallel tabs within the browser context:

```php
use Lablnet\StepWright\DTOs\ParallelTemplate;
use Lablnet\StepWright\DTOs\TabTemplate;

$parallel = new ParallelTemplate(
    templates: [
        new TabTemplate(tab: 'tab_1', steps: [...]),
        new TabTemplate(tab: 'tab_2', steps: [...]),
    ]
);

$results = StepWright::runScraper([$parallel], $options, $driver);
```

## `ParameterizedTemplate`

Generate dynamic templates based on input parameters (e.g., search keywords or product IDs):

```php
use Lablnet\StepWright\DTOs\ParameterizedTemplate;

$paramTemplate = new ParameterizedTemplate(
    baseTemplate: new TabTemplate(
        tab: 'search_{query}',
        steps: [
            new BaseStep(id: 'nav', action: 'navigate', object: 'https://example.com/search?q={query}'),
            new BaseStep(id: 'title', action: 'getTitle', key: 'title'),
        ]
    ),
    parameters: [
        ['query' => 'php'],
        ['query' => 'playwright'],
    ]
);

$results = StepWright::runScraper([$paramTemplate], $options, $driver);
```
