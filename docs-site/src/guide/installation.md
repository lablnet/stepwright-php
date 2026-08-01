# Installation

## Requirements

- **PHP**: 8.2 or higher
- **Composer**: Dependency manager for PHP

## Installing via Composer

Add `lablnet/stepwright` to your project dependencies:

```bash
composer require lablnet/stepwright
```

## Browser Driver Setup

StepWright PHP relies on `BrowserDriverInterface`. By default, you can use `PlaywrightDriver` or implement your own driver.

To install the Playwright PHP driver dependencies:

```bash
composer require playwright-php/playwright
```

Make sure Node.js and Playwright browsers are installed on your system:

```bash
npx playwright install chromium
```
