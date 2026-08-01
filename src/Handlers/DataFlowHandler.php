<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Handlers;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\Helpers;

class DataFlowHandler
{
    public static function handleReadData(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector
    ): void {
        $path = Helpers::replaceDataPlaceholders(is_string($step->value) ? $step->value : '', $collector);
        if (empty($path)) {
            throw new \InvalidArgumentException("readData requires a file path in 'value' for step '{$step->id}'");
        }

        if (!file_exists($path) && $step->dataType !== 'custom') {
            if ($step->continueOnEmpty) {
                echo "   ⚠️  File not found: {$path} - skipping\n";
                return;
            }
            throw new \RuntimeException("File not found: {$path}");
        }

        $data = null;
        $fmt = $step->dataType ?? 'text';

        try {
            if ($fmt === 'json') {
                $content = file_get_contents($path);
                $data = json_decode($content ?: '', true);
            } elseif ($fmt === 'csv') {
                $data = [];
                if (($handle = fopen($path, 'r')) !== false) {
                    $headers = fgetcsv($handle);
                    if ($headers) {
                        while (($row = fgetcsv($handle)) !== false) {
                            $data[] = array_combine($headers, $row);
                        }
                    }
                    fclose($handle);
                }
            } elseif ($fmt === 'custom' && is_callable($step->callback)) {
                $data = ($step->callback)($path, $step);
            } else {
                $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $data = array_map('trim', $lines ?: []);
            }

            $key = $step->key ?? $step->id;
            $collector[$key] = $data;
            $count = is_array($data) ? count($data) : 1;
            echo "   📂 Read {$count} items from {$path}\n";
        } catch (\Throwable $e) {
            if ($step->skipOnError) {
                echo "   ⚠️  readData failed for {$path} (skipping): {$e->getMessage()}\n";
            } else {
                throw $e;
            }
        }
    }

    public static function handleWriteData(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector
    ): void {
        $path = Helpers::replaceDataPlaceholders(is_string($step->value) ? $step->value : '', $collector);
        if (empty($path)) {
            throw new \InvalidArgumentException("writeData requires a file path in 'value' for step '{$step->id}'");
        }

        $dataToWrite = $step->key ? ($collector[$step->key] ?? null) : $collector;
        if ($dataToWrite === null) {
            echo "   ⚠️  No data found for key '{$step->key}' - skipping write\n";
            return;
        }

        Helpers::ensureDir($path);
        $fmt = $step->dataType ?? 'text';
        $append = ($step->continueOnEmpty !== false);

        try {
            if ($fmt === 'json') {
                file_put_contents($path, json_encode($dataToWrite, JSON_PRETTY_PRINT));
            } elseif ($fmt === 'csv') {
                if (!is_array($dataToWrite) || !array_is_list($dataToWrite)) {
                    $dataToWrite = [$dataToWrite];
                }
                if (!empty($dataToWrite)) {
                    $fileExists = file_exists($path);
                    $handle = fopen($path, 'a');
                    if ($handle !== false) {
                        if (!$fileExists && is_array($dataToWrite[0])) {
                            fputcsv($handle, array_keys($dataToWrite[0]));
                        }
                        foreach ($dataToWrite as $row) {
                            if (is_array($row)) {
                                fputcsv($handle, $row);
                            }
                        }
                        fclose($handle);
                    }
                }
            } elseif ($fmt === 'custom' && is_callable($step->callback)) {
                ($step->callback)($path, $dataToWrite, $step);
            } else {
                $flags = $append ? FILE_APPEND : 0;
                if (is_array($dataToWrite)) {
                    $content = implode("\n", array_map(fn($item) => is_array($item) ? json_encode($item) : (string) $item, $dataToWrite)) . "\n";
                    file_put_contents($path, $content, $flags);
                } else {
                    file_put_contents($path, ((string) $dataToWrite) . "\n", $flags);
                }
            }

            echo "   💾 Saved data to {$path}\n";
        } catch (\Throwable $e) {
            if ($step->skipOnError) {
                echo "   ⚠️  writeData failed for {$path} (skipping): {$e->getMessage()}\n";
            } else {
                throw $e;
            }
        }
    }

    public static function handleCustomCallback(
        BrowserDriverInterface $driver,
        mixed $page,
        BaseStep $step,
        array &$collector
    ): void {
        if (!is_callable($step->callback)) {
            throw new \InvalidArgumentException("Action 'custom' requires a 'callback' in step '{$step->id}'");
        }

        echo "   ⚡ Executing custom callback for '{$step->id}'\n";
        $result = ($step->callback)($page, $collector, $step);

        if ($step->key && $result !== null) {
            $collector[$step->key] = $result;
        }
    }
}
