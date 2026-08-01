<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Drivers;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Playwright\PlaywrightClient;
use Playwright\PlaywrightFactory;

class PlaywrightDriver implements BrowserDriverInterface
{
    private static ?PlaywrightClient $playwrightInstance = null;
    private mixed $currentBrowser = null;

    public function launch(array $options = []): mixed
    {
        if (self::$playwrightInstance === null) {
            if (class_exists(PlaywrightFactory::class)) {
                self::$playwrightInstance = PlaywrightFactory::create();
            }
        }

        if (self::$playwrightInstance !== null) {
            $browserType = strtolower((string) ($options['browser'] ?? $options['type'] ?? 'chromium'));
            $builder = match ($browserType) {
                'firefox' => self::$playwrightInstance->firefox(),
                'webkit', 'safari' => self::$playwrightInstance->webkit(),
                default => self::$playwrightInstance->chromium(),
            };

            if (isset($options['headless'])) {
                $builder->withHeadless((bool) $options['headless']);
            }
            if (isset($options['slowMo']) && is_numeric($options['slowMo'])) {
                $builder->withSlowMo((int) $options['slowMo']);
            }
            if (isset($options['args']) && is_array($options['args'])) {
                $builder->withArgs(array_filter($options['args'], 'is_string'));
            }

            $this->currentBrowser = $builder->launch();
            return $this->currentBrowser;
        }

        throw new \RuntimeException('Playwright instance could not be initialized. Please ensure playwright-php/playwright is installed.');
    }

    public function newContext(array $options = []): mixed
    {
        if ($this->currentBrowser && method_exists($this->currentBrowser, 'newContext')) {
            return $this->currentBrowser->newContext($options);
        }
        throw new \RuntimeException('No active browser instance to create context.');
    }

    public function newPage(mixed $context = null): mixed
    {
        if ($context && method_exists($context, 'newPage')) {
            return $context->newPage();
        }
        if ($this->currentBrowser && method_exists($this->currentBrowser, 'newPage')) {
            return $this->currentBrowser->newPage();
        }
        throw new \RuntimeException('Cannot create new page.');
    }

    public function closePage(mixed $page): void
    {
        if ($page && method_exists($page, 'close')) {
            $page->close();
        }
    }

    public function closeContext(mixed $context): void
    {
        if ($context && method_exists($context, 'close')) {
            $context->close();
        }
    }

    public function closeBrowser(mixed $browser = null): void
    {
        $target = $browser ?? $this->currentBrowser;
        if ($target && method_exists($target, 'close')) {
            $target->close();
        }
        if ($target === $this->currentBrowser) {
            $this->currentBrowser = null;
        }
    }

    public function shutdown(): void
    {
        if (self::$playwrightInstance !== null && method_exists(self::$playwrightInstance, 'close')) {
            self::$playwrightInstance->close();
            self::$playwrightInstance = null;
        }
    }

    public function goto(mixed $page, string $url, array $options = []): void
    {
        if (method_exists($page, 'goto')) {
            $page->goto($url, $options);
        }
    }

    public function reload(mixed $page, array $options = []): void
    {
        if (method_exists($page, 'reload')) {
            $page->reload($options);
        }
    }

    public function getTitle(mixed $page): string
    {
        if (method_exists($page, 'title')) {
            return (string) $page->title();
        }
        return '';
    }

    public function getUrl(mixed $page): string
    {
        if (method_exists($page, 'url')) {
            return (string) $page->url();
        }
        return '';
    }

    public function waitForTimeout(mixed $page, int $milliseconds): void
    {
        if (method_exists($page, 'waitForTimeout')) {
            $page->waitForTimeout($milliseconds);
        } else {
            usleep($milliseconds * 1000);
        }
    }

    public function waitForLoadState(mixed $page, string $state = 'load', ?int $timeout = null): void
    {
        if (method_exists($page, 'waitForLoadState')) {
            $page->waitForLoadState($state, array_filter(['timeout' => $timeout]));
        }
    }

    public function locator(mixed $context, string $selector): mixed
    {
        if ($context && method_exists($context, 'locator')) {
            return $context->locator($selector);
        }
        return null;
    }

    public function click(mixed $locator, array $options = []): void
    {
        if ($locator && method_exists($locator, 'click')) {
            $locator->click($options);
        }
    }

    public function dblclick(mixed $locator, array $options = []): void
    {
        if ($locator && method_exists($locator, 'dblclick')) {
            $locator->dblclick($options);
        }
    }

    public function check(mixed $locator, array $options = []): void
    {
        if ($locator && method_exists($locator, 'check')) {
            $locator->check($options);
        }
    }

    public function fill(mixed $locator, string $value, array $options = []): void
    {
        if ($locator && method_exists($locator, 'fill')) {
            $locator->fill($value, $options);
        }
    }

    public function type(mixed $locator, string $text, array $options = []): void
    {
        if ($locator && method_exists($locator, 'type')) {
            $locator->type($text, $options);
        } elseif ($locator && method_exists($locator, 'fill')) {
            $locator->fill($text, $options);
        }
    }

    public function clear(mixed $locator, array $options = []): void
    {
        if ($locator && method_exists($locator, 'clear')) {
            $locator->clear($options);
        } elseif ($locator && method_exists($locator, 'fill')) {
            $locator->fill('', $options);
        }
    }

    public function hover(mixed $locator, array $options = []): void
    {
        if ($locator && method_exists($locator, 'hover')) {
            $locator->hover($options);
        }
    }

    public function selectOption(mixed $locator, mixed $values, array $options = []): void
    {
        if ($locator && method_exists($locator, 'selectOption')) {
            $locator->selectOption($values, $options);
        }
    }

    public function dragTo(mixed $sourceLocator, mixed $targetLocator, array $options = []): void
    {
        if ($sourceLocator && method_exists($sourceLocator, 'dragTo')) {
            $sourceLocator->dragTo($targetLocator, $options);
        }
    }

    public function setInputFiles(mixed $locator, mixed $files, array $options = []): void
    {
        if ($locator && method_exists($locator, 'setInputFiles')) {
            $locator->setInputFiles($files, $options);
        }
    }

    public function textContent(mixed $locator): ?string
    {
        if ($locator && method_exists($locator, 'textContent')) {
            return $locator->textContent();
        }
        return null;
    }

    public function innerHtml(mixed $locator): string
    {
        if ($locator && method_exists($locator, 'innerHTML')) {
            return (string) $locator->innerHTML();
        }
        return '';
    }

    public function innerText(mixed $locator): string
    {
        if ($locator && method_exists($locator, 'innerText')) {
            return (string) $locator->innerText();
        }
        return (string) ($this->textContent($locator) ?? '');
    }

    public function inputValue(mixed $locator): string
    {
        if ($locator && method_exists($locator, 'inputValue')) {
            return (string) $locator->inputValue();
        }
        return '';
    }

    public function getAttribute(mixed $locator, string $name): ?string
    {
        if ($locator && method_exists($locator, 'getAttribute')) {
            return $locator->getAttribute($name);
        }
        return null;
    }

    public function count(mixed $locator): int
    {
        if ($locator && method_exists($locator, 'count')) {
            return (int) $locator->count();
        }
        return 0;
    }

    public function nth(mixed $locator, int $index): mixed
    {
        if ($locator && method_exists($locator, 'nth')) {
            return $locator->nth($index);
        }
        return null;
    }

    public function first(mixed $locator): mixed
    {
        if ($locator && method_exists($locator, 'first')) {
            return $locator->first();
        }
        return $locator;
    }

    public function scrollIntoView(mixed $locator): void
    {
        if ($locator && method_exists($locator, 'scrollIntoViewIfNeeded')) {
            $locator->scrollIntoViewIfNeeded();
        }
    }

    public function isVisible(mixed $locator): bool
    {
        if ($locator && method_exists($locator, 'isVisible')) {
            return (bool) $locator->isVisible();
        }
        return false;
    }

    public function isEnabled(mixed $locator): bool
    {
        if ($locator && method_exists($locator, 'isEnabled')) {
            return (bool) $locator->isEnabled();
        }
        return true;
    }

    public function evaluate(mixed $context, string $expression, mixed $arg = null): mixed
    {
        if ($context && method_exists($context, 'evaluate')) {
            return $context->evaluate($expression, $arg);
        }
        return null;
    }

    public function screenshot(mixed $pageOrLocator, array $options = []): string
    {
        if ($pageOrLocator && method_exists($pageOrLocator, 'screenshot')) {
            $pageOrLocator->screenshot($options);
            return $options['path'] ?? '';
        }
        return '';
    }

    public function waitForSelector(mixed $context, string $selector, array $options = []): void
    {
        if ($context && method_exists($context, 'waitForSelector')) {
            $context->waitForSelector($selector, $options);
        }
    }

    public function frameLocator(mixed $context, string $selector): mixed
    {
        if ($context && method_exists($context, 'frameLocator')) {
            return $context->frameLocator($selector);
        }
        return null;
    }
}
