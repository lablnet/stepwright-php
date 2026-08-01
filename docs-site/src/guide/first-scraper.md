# Your First Scraper

Creating a web scraper with StepWright PHP involves defining a `TabTemplate` with your steps and running it via `StepWright::runScraper()`.

## Example Code

```php
<?php

use Lablnet\StepWright\StepWright;
use Lablnet\StepWright\DTOs\TabTemplate;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\DTOs\RunOptions;
use Lablnet\StepWright\Drivers\PlaywrightDriver;

require_once __DIR__ . '/vendor/autoload.php';

// 1. Instantiate the browser driver
$driver = new PlaywrightDriver();

// 2. Create the scraping template
$template = new TabTemplate(
    tab: 'quotes_scraper',
    steps: [
        new BaseStep(
            id: 'navigate_step',
            action: 'navigate',
            object: 'https://quotes.toscrape.com/'
        ),
        new BaseStep(
            id: 'extract_title',
            action: 'getTitle',
            key: 'page_title'
        ),
    ]
);

// 3. Execute the scraper
$results = StepWright::runScraper(
    templates: [$template],
    options: new RunOptions(browser: ['headless' => true]),
    driver: $driver
);

print_r($results);
```

## Step Explanation

1. **`navigate`**: Directs the browser page to the specified target URL.
2. **`getTitle`**: Obtains the document title and stores it under key `page_title` in the collected results.
