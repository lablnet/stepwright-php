<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Handlers;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\Helpers;

class PageActionHandler
{
    public static function handleReload(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        $waitUntil = is_string($step->value) ? $step->value : 'load';
        echo "   🔄 Reloading page (wait_until={$waitUntil})\n";
        $driver->reload($page, ['waitUntil' => $waitUntil]);
    }

    public static function handleGetUrl(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        $url = $driver->getUrl($page);
        $key = $step->key ?? $step->id ?? 'url';
        $collector[$key] = $url;
        echo "   🔗 Current URL: {$url}\n";
        echo "   📋 Stored in collector[{$key}]\n";
    }

    public static function handleGetTitle(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        $title = $driver->getTitle($page);
        $key = $step->key ?? $step->id ?? 'title';
        $collector[$key] = $title;
        echo "   📄 Page title: {$title}\n";
        echo "   📋 Stored in collector[{$key}]\n";
    }

    public static function handleScreenshot(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        if (empty($step->value) || !is_string($step->value)) {
            throw new \InvalidArgumentException("screenshot step {$step->id} requires 'value' as target filepath");
        }

        $targetPath = Helpers::replaceDataPlaceholders($step->value, $collector) ?? $step->value;
        Helpers::ensureDir($targetPath);

        try {
            if ($step->object) {
                $loc = Helpers::locatorFor($driver, $page, $step->objectType, $step->object);
                if ($driver->count($loc) === 0) {
                    echo "   ⚠️  Element not found: {$step->object} - taking full page screenshot\n";
                    $driver->screenshot($page, ['path' => $targetPath, 'fullPage' => true]);
                } else {
                    $driver->screenshot($driver->first($loc), ['path' => $targetPath]);
                    echo "   📸 Screenshot saved (element) to {$targetPath}\n";
                }
            } else {
                $fullPage = ($step->dataType === 'full');
                $driver->screenshot($page, ['path' => $targetPath, 'fullPage' => $fullPage]);
                echo "   📸 Screenshot saved (" . ($fullPage ? 'full page' : 'viewport') . ") to {$targetPath}\n";
            }

            if ($step->key) {
                $collector[$step->key] = $targetPath;
            }
        } catch (\Throwable $e) {
            echo "   ⚠️  Screenshot failed: {$e->getMessage()}\n";
            if ($step->terminateOnError) {
                throw $e;
            }
        }
    }

    public static function handleWaitForSelector(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        if (empty($step->object)) {
            throw new \InvalidArgumentException("waitForSelector step requires object locator");
        }

        $timeout = $step->wait ?? 30000;
        $state = is_string($step->value) ? $step->value : 'visible';

        echo "   ⏳ Waiting for selector {$step->object} (state={$state}, timeout={$timeout}ms)\n";

        try {
            $context = Helpers::locatorFor($driver, $page, $step->objectType, $step->object);
            $driver->waitForSelector($page, $step->object, ['state' => $state, 'timeout' => $timeout]);
            echo "   ✅ Selector found: {$step->object}\n";

            if ($step->key) {
                $collector[$step->key] = true;
            }
        } catch (\Throwable $e) {
            echo "   ⚠️  Wait for selector failed: {$step->object} - {$e->getMessage()}\n";
            if ($step->key) {
                $collector[$step->key] = false;
            }
            if ($step->terminateOnError) {
                throw $e;
            }
        }
    }

    public static function handleGetMeta(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        $metaName = $step->object;
        $key = $step->key ?? $step->id ?? 'meta';

        if ($metaName) {
            $metaContent = $driver->evaluate($page, "
                (metaName) => {
                    const metas = document.querySelectorAll('meta');
                    for (const meta of metas) {
                        if (meta.getAttribute('name') === metaName || meta.getAttribute('property') === metaName) {
                            return meta.getAttribute('content');
                        }
                    }
                    return null;
                }
            ", $metaName);
            $collector[$key] = $metaContent;
            echo "   📋 Meta tag '{$metaName}': {$metaContent}\n";
        } else {
            $allMeta = $driver->evaluate($page, "
                () => {
                    const metas = {};
                    document.querySelectorAll('meta').forEach(meta => {
                        const name = meta.getAttribute('name') || meta.getAttribute('property');
                        const content = meta.getAttribute('content');
                        if (name && content) {
                            metas[name] = content;
                        }
                    });
                    return metas;
                }
            ");
            $collector[$key] = $allMeta;
            echo "   📋 Retrieved meta tags\n";
        }
    }

    public static function handleGetCookies(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        $cookies = $driver->evaluate($page, "() => document.cookie");
        $key = $step->key ?? $step->id ?? 'cookies';
        $collector[$key] = $cookies;
        echo "   🍪 Retrieved cookies\n";
    }

    public static function handleSetCookies(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        if (empty($step->object) || empty($step->value)) {
            throw new \InvalidArgumentException("setCookies step requires 'object' (name) and 'value'");
        }

        $cookieName = Helpers::replaceDataPlaceholders($step->object, $collector) ?? $step->object;
        $cookieValue = Helpers::replaceDataPlaceholders(is_string($step->value) ? $step->value : '', $collector) ?? $step->value;

        $driver->evaluate($page, "document.cookie = " . json_encode("{$cookieName}={$cookieValue}; path=/"));
        echo "   🍪 Set cookie '{$cookieName}'\n";
    }

    public static function handleGetLocalStorage(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        $keyName = $step->object;
        $key = $step->key ?? $step->id ?? 'localStorage';

        if ($keyName) {
            $val = $driver->evaluate($page, "() => localStorage.getItem(" . json_encode($keyName) . ")");
            $collector[$key] = $val;
            echo "   💾 localStorage['{$keyName}']: {$val}\n";
        } else {
            $all = $driver->evaluate($page, "
                () => {
                    const storage = {};
                    for (let i = 0; i < localStorage.length; i++) {
                        const k = localStorage.key(i);
                        storage[k] = localStorage.getItem(k);
                    }
                    return storage;
                }
            ");
            $collector[$key] = $all;
            echo "   💾 Retrieved localStorage\n";
        }
    }

    public static function handleSetLocalStorage(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        if (empty($step->object) || $step->value === null) {
            throw new \InvalidArgumentException("setLocalStorage step requires 'object' (key) and 'value'");
        }

        $keyName = Helpers::replaceDataPlaceholders($step->object, $collector) ?? $step->object;
        $value = Helpers::replaceDataPlaceholders((string) $step->value, $collector) ?? (string) $step->value;

        $driver->evaluate($page, "() => localStorage.setItem(" . json_encode($keyName) . ", " . json_encode($value) . ")");
        echo "   💾 Set localStorage['{$keyName}'] = '{$value}'\n";
    }

    public static function handleGetSessionStorage(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        $keyName = $step->object;
        $key = $step->key ?? $step->id ?? 'sessionStorage';

        if ($keyName) {
            $val = $driver->evaluate($page, "() => sessionStorage.getItem(" . json_encode($keyName) . ")");
            $collector[$key] = $val;
            echo "   💾 sessionStorage['{$keyName}']: {$val}\n";
        } else {
            $all = $driver->evaluate($page, "
                () => {
                    const storage = {};
                    for (let i = 0; i < sessionStorage.length; i++) {
                        const k = sessionStorage.key(i);
                        storage[k] = sessionStorage.getItem(k);
                    }
                    return storage;
                }
            ");
            $collector[$key] = $all;
            echo "   💾 Retrieved sessionStorage\n";
        }
    }

    public static function handleSetSessionStorage(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        if (empty($step->object) || $step->value === null) {
            throw new \InvalidArgumentException("setSessionStorage step requires 'object' (key) and 'value'");
        }

        $keyName = Helpers::replaceDataPlaceholders($step->object, $collector) ?? $step->object;
        $value = Helpers::replaceDataPlaceholders((string) $step->value, $collector) ?? (string) $step->value;

        $driver->evaluate($page, "() => sessionStorage.setItem(" . json_encode($keyName) . ", " . json_encode($value) . ")");
        echo "   💾 Set sessionStorage['{$keyName}'] = '{$value}'\n";
    }

    public static function handleGetViewportSize(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        $viewport = $driver->evaluate($page, "() => ({ width: window.innerWidth, height: window.innerHeight })");
        $key = $step->key ?? $step->id ?? 'viewportSize';
        $collector[$key] = $viewport;
        echo "   📐 Viewport size retrieved\n";
    }

    public static function handleSetViewportSize(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        if (empty($step->value) || !is_string($step->value)) {
            throw new \InvalidArgumentException("setViewportSize step requires 'value' as dimensions (e.g. '1920x1080')");
        }

        $dims = str_replace(['x', ' '], ',', $step->value);
        $parts = explode(',', $dims);
        if (count($parts) >= 2) {
            $w = (int) $parts[0];
            $h = (int) $parts[1];
            $driver->evaluate($page, "() => window.resizeTo({$w}, {$h})");
            echo "   📐 Set viewport size to {$w}x{$h}\n";
        }
    }

    public static function handleEvaluate(BrowserDriverInterface $driver, mixed $page, BaseStep $step, array &$collector): void
    {
        if (empty($step->value) || !is_string($step->value)) {
            throw new \InvalidArgumentException("evaluate step requires 'value' as JavaScript code");
        }

        $jsCode = Helpers::replaceDataPlaceholders($step->value, $collector) ?? $step->value;
        echo "   🔧 Evaluating JavaScript...\n";

        try {
            $result = $driver->evaluate($page, $jsCode);
            if ($step->key) {
                $collector[$step->key] = $result;
                echo "   📋 Result stored in collector[{$step->key}]\n";
            }
        } catch (\Throwable $e) {
            echo "   ⚠️  JavaScript evaluation failed: {$e->getMessage()}\n";
            if ($step->key) {
                $collector[$step->key] = null;
            }
            if ($step->terminateOnError) {
                throw $e;
            }
        }
    }
}
