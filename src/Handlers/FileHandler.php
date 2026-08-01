<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Handlers;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\Helpers;
use Lablnet\StepWright\Scraper;

class FileHandler
{
    public static function handleEventDownload(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector
    ): void {
        if (empty($step->value) || !is_string($step->value)) {
            throw new \InvalidArgumentException("download step {$step->id} requires 'value' as target filepath");
        }

        $key = $step->key ?? $step->id ?? 'file';
        $savedPath = null;

        try {
            $target = Scraper::elem($driver, $page, $step->objectType ?? 'tag', $step->object ?? '');
            if ($driver->isVisible($driver->first($target))) {
                $driver->click($driver->first($target));
                Helpers::ensureDir($step->value);
                $savedPath = $step->value;
                echo "   📥 Saved to {$savedPath}\n";
            } else {
                echo "   📥 Element not visible or not found: {$step->object}\n";
            }
        } catch (\Throwable $e) {
            echo "   📥 Download failed for {$step->object}: {$e->getMessage()}\n";
        } finally {
            $collector[$key] = $savedPath;
        }
    }

    public static function handleSavePDF(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector
    ): void {
        if (empty($step->value) || !is_string($step->value)) {
            throw new \InvalidArgumentException("savePDF step {$step->id} requires 'value' as target filepath");
        }

        $collectorKey = $step->key ?? $step->id ?? 'file';
        $savedPath = null;
        $targetPathBase = $step->value;

        try {
            $resolvedPath = Helpers::replaceDataPlaceholders($targetPathBase, $collector) ?? $targetPathBase;
            Helpers::ensureDir($resolvedPath);

            $pdfUrl = $driver->evaluate($page, "
                () => {
                    const embed = document.querySelector('embed[type=\"application/pdf\"]');
                    if (embed && embed.getAttribute('src')) return embed.getAttribute('src');

                    const objectEl = document.querySelector('object[type=\"application/pdf\"]');
                    if (objectEl && objectEl.getAttribute('data')) return objectEl.getAttribute('data');

                    const iframe = Array.from(document.querySelectorAll('iframe')).find(f => {
                        const s = f.getAttribute('src') || '';
                        return /\\.pdf/i.test(s) || s.includes('pdf');
                    });
                    if (iframe && iframe.getAttribute('src')) return iframe.getAttribute('src');

                    return null;
                }
            ");

            if ($pdfUrl && is_string($pdfUrl)) {
                if (!str_starts_with($pdfUrl, 'http')) {
                    $currentUrl = rtrim($driver->getUrl($page), '/');
                    $pdfUrl = $currentUrl . '/' . ltrim($pdfUrl, '/');
                }

                $content = @file_get_contents($pdfUrl);
                if ($content !== false) {
                    file_put_contents($resolvedPath, $content);
                    $savedPath = $resolvedPath;
                    echo "   📄 PDF saved to {$resolvedPath} (from {$pdfUrl})\n";
                }
            }

            if (!$savedPath) {
                $driver->screenshot($page, ['path' => $resolvedPath, 'fullPage' => true]);
                $savedPath = $resolvedPath;
                echo "   📄 PDF fallback screenshot saved to {$resolvedPath}\n";
            }
        } catch (\Throwable $e) {
            echo "   📄 savePDF failed: {$e->getMessage()}\n";
        } finally {
            $collector[$collectorKey] = $savedPath;
        }
    }

    public static function handleDownloadPDF(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector
    ): void {
        if (empty($step->object)) {
            throw new \InvalidArgumentException("downloadPDF requires object locator");
        }
        if (empty($step->value) || !is_string($step->value)) {
            throw new \InvalidArgumentException("downloadPDF step {$step->id} requires 'value' as target filepath");
        }

        $key = $step->key ?? $step->id ?? 'file';
        $savedPath = null;

        try {
            $link = Helpers::locatorFor($driver, $page, $step->objectType, $step->object);
            if ($driver->count($link) === 0) {
                echo "   ⚠️  PDF link not found: {$step->object}\n";
                $collector[$key] = null;
                return;
            }

            $first = $driver->first($link);
            $href = $driver->getAttribute($first, 'href');

            if ($href && !str_starts_with($href, 'javascript')) {
                if (!str_starts_with($href, 'http')) {
                    $href = rtrim($driver->getUrl($page), '/') . '/' . ltrim($href, '/');
                }

                $content = @file_get_contents($href);
                if ($content !== false) {
                    $resolved = Helpers::replaceDataPlaceholders($step->value, $collector) ?? $step->value;
                    Helpers::ensureDir($resolved);
                    file_put_contents($resolved, $content);
                    $savedPath = $resolved;
                    echo "   📄 File saved to {$resolved}\n";
                }
            }
        } catch (\Throwable $e) {
            echo "   📄 downloadPDF failed: {$e->getMessage()}\n";
        } finally {
            $collector[$key] = $savedPath;
        }
    }
}
