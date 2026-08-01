# Pagination & Scrolling

StepWright PHP provides built-in pagination handling via `PaginationConfig`.

## Pagination Configuration (`PaginationConfig`)

```php
use Lablnet\StepWright\DTOs\PaginationConfig;
use Lablnet\StepWright\DTOs\NextButtonConfig;

$pagination = new PaginationConfig(
    maxPages: 5,
    paginationFirst: false,
    nextButton: new NextButtonConfig(
        objectType: 'css',
        object: 'li.next > a',
        wait: 1000
    )
);
```

## `maxPages` Limit

Set `maxPages` to cap the maximum number of paginated pages scraped during template execution. If `maxPages` is `null` or `0`, scraping continues until no next button is found or pagination stops.

## Scrolling Options

Automatic scrolling before or after extracting data:

```php
$pagination = new PaginationConfig(
    scrollBefore: true,
    scrollAfter: true,
    scrollTimeout: 2000,
    maxPages: 10
);
```
