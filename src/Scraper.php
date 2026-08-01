<?php

declare(strict_types=1);

namespace Lablnet\StepWright;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\Drivers\PlaywrightDriver;
use Lablnet\StepWright\Enums\SelectorType;

class Scraper
{
    private static ?BrowserDriverInterface $defaultDriver = null;

    public static function getDefaultDriver(): BrowserDriverInterface
    {
        if (self::$defaultDriver === null) {
            self::$defaultDriver = new PlaywrightDriver();
        }
        return self::$defaultDriver;
    }

    public static function setDefaultDriver(BrowserDriverInterface $driver): void
    {
        self::$defaultDriver = $driver;
    }

    public static function getBrowser(?BrowserDriverInterface $driver = null, array $params = []): mixed
    {
        $d = $driver ?? self::getDefaultDriver();
        return $d->launch($params);
    }

    public static function shutdownPlaywright(?BrowserDriverInterface $driver = null): void
    {
        $d = $driver ?? self::getDefaultDriver();
        $d->shutdown();
    }

    public static function navigate(BrowserDriverInterface $driver, mixed $page, string $url, int $wait = 0): mixed
    {
        if (empty($url)) {
            throw new \InvalidArgumentException('Url is required');
        }

        $driver->goto($page, $url, ['waitUntil' => 'networkidle']);

        if ($wait > 0) {
            $driver->waitForTimeout($page, $wait);
        }

        return $page;
    }

    public static function elem(BrowserDriverInterface $driver, mixed $page, SelectorType|string $type, string $selector, int $wait = 0): mixed
    {
        if (empty($selector)) {
            throw new \InvalidArgumentException('Selector is required');
        }

        $element = Helpers::locatorFor($driver, $page, $type, $selector);

        if ($wait > 0) {
            $driver->waitForTimeout($page, $wait);
        }

        return $element;
    }

    public static function input(BrowserDriverInterface $driver, mixed $page, SelectorType|string $type, string $selector, string $value, int $wait = 0): mixed
    {
        $element = self::elem($driver, $page, $type, $selector, $wait);
        $first = $driver->first($element);
        $driver->type($first, $value);
        return $page;
    }

    public static function click(BrowserDriverInterface $driver, mixed $page, SelectorType|string $type, string $selector): mixed
    {
        $element = self::elem($driver, $page, $type, $selector);
        $first = $driver->first($element);
        $driver->click($first);
        return $page;
    }

    public static function doubleClick(BrowserDriverInterface $driver, mixed $page, SelectorType|string $type, string $selector): mixed
    {
        $element = self::elem($driver, $page, $type, $selector);
        $first = $driver->first($element);
        $driver->dblclick($first);
        return $page;
    }

    public static function clickCheckBox(BrowserDriverInterface $driver, mixed $page, SelectorType|string $type, string $selector): mixed
    {
        $element = self::elem($driver, $page, $type, $selector);
        $first = $driver->first($element);
        $driver->check($first);
        return $page;
    }

    public static function getData(
        BrowserDriverInterface $driver,
        mixed $page,
        SelectorType|string $type,
        string $selector,
        string $dataType = 'default',
        int $wait = 0,
        ?string $attributeName = null
    ): string {
        $finalSelector = $selector;
        $finalAttrName = $attributeName;

        if ($dataType === 'attribute') {
            if (preg_match('/\/@(\w+)$/', $selector, $matches)) {
                $finalAttrName = $matches[1];
                $finalSelector = preg_replace('/\/@\w+$/', '', $selector);
            }
            if (!$finalAttrName) {
                throw new \InvalidArgumentException(
                    'Attribute name is required for attribute data type. Use selector like //element/@attribute or pass attributeName.'
                );
            }
        }

        $element = self::elem($driver, $page, $type, $finalSelector, $wait);
        $first = $driver->first($element);

        if ($dataType === 'text') {
            return $driver->textContent($first) ?? '';
        } elseif ($dataType === 'html') {
            return $driver->innerHtml($first);
        } elseif ($dataType === 'value') {
            return $driver->inputValue($first);
        } elseif ($dataType === 'attribute') {
            return $driver->getAttribute($first, $finalAttrName) ?? '';
        } else {
            return $driver->innerText($first);
        }
    }
}
