<div align="center">
  <h1>StepWright PHP</h1>
</div>

<p align="center">
  <strong>A declarative, step-by-step approach to web automation and data extraction with Playwright in PHP.</strong>
</p>

## Overview

StepWright completely abstracts away the complexity of raw browser automation scripts. Instead of writing imperative browser commands buried deep in loops and error handlers, StepWright allows you to define **declarative scraping workflows** using PHP objects or arrays.

## Installation

```bash
composer require lablnet/stepwright-php
```

Make sure Node.js and Playwright browsers are installed:

```bash
npx playwright install chromium
```

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use Lablnet\StepWright\StepWright;
use Lablnet\StepWright\DTOs\TabTemplate;
use Lablnet\StepWright\DTOs\BaseStep;

$flow = new TabTemplate(
    tab: 'search',
    steps: [
        new BaseStep(id: 'nav', action: 'navigate', value: 'https://news.ycombinator.com'),
        new BaseStep(
            id: 'extract',
            action: 'foreach',
            object: '.athing',
            subSteps: [
                new BaseStep(id: 'title', action: 'data', object: '.titleline', key: 'title')
            ]
        )
    ]
);

$results = StepWright::runScraper([$flow]);
print_r($results);
```

## Features

- **35 Declarative Step Actions**: Navigation, clicks, input, data extraction, scroll, PDF saving, file downloads, storage/cookies, iframe handling, custom callbacks.
- **Dynamic Placeholders**: Replace index placeholders (`{{ i }}`, `{{ i_plus1 }}`) and data placeholders (`{{ key }}`).
- **Advanced Pagination**: Next button pagination, infinite scroll pagination, `paginationFirst`, and `paginateAllFirst`.
- **Parallel & Parameterized Workflows**: Run multiple templates concurrently or generate parameterized workflows for search terms.
- **Custom Driver Support**: Pluggable `BrowserDriverInterface` to support Playwright or alternative browser drivers.

## License

MIT License. Developed with ❤️ by Muhammad Umer Farooq (@lablnet).
