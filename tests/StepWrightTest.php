<?php

declare(strict_types=1);

namespace Lablnet\StepWright\Tests;

use Lablnet\StepWright\Contracts\BrowserDriverInterface;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\DTOs\NextButtonConfig;
use Lablnet\StepWright\DTOs\PaginationConfig;
use Lablnet\StepWright\DTOs\ParallelTemplate;
use Lablnet\StepWright\DTOs\ParameterizedTemplate;
use Lablnet\StepWright\DTOs\RunOptions;
use Lablnet\StepWright\DTOs\TabTemplate;
use Lablnet\StepWright\Enums\ActionType;
use Lablnet\StepWright\Enums\DataType;
use Lablnet\StepWright\Enums\SelectorType;
use Lablnet\StepWright\Helpers;
use Lablnet\StepWright\StepExecutor;
use Lablnet\StepWright\StepWright;

use PHPUnit\Framework\TestCase;

class MockDriver implements BrowserDriverInterface
{
    public array $logs = [];
    public array $evalResults = [];
    public array $locatorCounts = [];
    public array $textContents = [];

    public function launch(array $options = []): mixed
    {
        $this->logs[] = ['launch', $options];
        return 'mock_browser';
    }

    public function newContext(array $options = []): mixed
    {
        $this->logs[] = ['newContext', $options];
        return 'mock_context';
    }

    public function newPage(mixed $context = null): mixed
    {
        $this->logs[] = ['newPage', $context];
        return 'mock_page';
    }

    public function closePage(mixed $page): void
    {
        $this->logs[] = ['closePage', $page];
    }

    public function closeContext(mixed $context): void
    {
        $this->logs[] = ['closeContext', $context];
    }

    public function closeBrowser(mixed $browser = null): void
    {
        $this->logs[] = ['closeBrowser', $browser];
    }

    public function shutdown(): void
    {
        $this->logs[] = ['shutdown'];
    }

    public function goto(mixed $page, string $url, array $options = []): void
    {
        $this->logs[] = ['goto', $url, $options];
    }

    public function reload(mixed $page, array $options = []): void
    {
        $this->logs[] = ['reload', $options];
    }

    public function getTitle(mixed $page): string
    {
        return 'Mock Title';
    }

    public function getUrl(mixed $page): string
    {
        return 'https://example.com';
    }

    public function waitForTimeout(mixed $page, int $milliseconds): void
    {
        $this->logs[] = ['waitForTimeout', $milliseconds];
    }

    public function waitForLoadState(mixed $page, string $state = 'load', ?int $timeout = null): void
    {
        $this->logs[] = ['waitForLoadState', $state, $timeout];
    }

    public function locator(mixed $context, string $selector): mixed
    {
        $this->logs[] = ['locator', $selector];
        return ['selector' => $selector];
    }

    public function click(mixed $locator, array $options = []): void
    {
        $this->logs[] = ['click', $locator, $options];
    }

    public function dblclick(mixed $locator, array $options = []): void
    {
        $this->logs[] = ['dblclick', $locator, $options];
    }

    public function check(mixed $locator, array $options = []): void
    {
        $this->logs[] = ['check', $locator, $options];
    }

    public function fill(mixed $locator, string $value, array $options = []): void
    {
        $this->logs[] = ['fill', $locator, $value, $options];
    }

    public function type(mixed $locator, string $text, array $options = []): void
    {
        $this->logs[] = ['type', $locator, $text, $options];
    }

    public function clear(mixed $locator, array $options = []): void
    {
        $this->logs[] = ['clear', $locator, $options];
    }

    public function hover(mixed $locator, array $options = []): void
    {
        $this->logs[] = ['hover', $locator, $options];
    }

    public function selectOption(mixed $locator, mixed $values, array $options = []): void
    {
        $this->logs[] = ['selectOption', $locator, $values, $options];
    }

    public function dragTo(mixed $sourceLocator, mixed $targetLocator, array $options = []): void
    {
        $this->logs[] = ['dragTo', $sourceLocator, $targetLocator, $options];
    }

    public function setInputFiles(mixed $locator, mixed $files, array $options = []): void
    {
        $this->logs[] = ['setInputFiles', $locator, $files, $options];
    }

    public function textContent(mixed $locator): ?string
    {
        $sel = is_array($locator) ? ($locator['selector'] ?? '') : '';
        return $this->textContents[$sel] ?? 'Mock Text';
    }

    public function innerHtml(mixed $locator): string
    {
        return '<div>Mock HTML</div>';
    }

    public function innerText(mixed $locator): string
    {
        return 'Mock Text';
    }

    public function inputValue(mixed $locator): string
    {
        return 'Mock Value';
    }

    public function getAttribute(mixed $locator, string $name): ?string
    {
        return 'mock_attr_value';
    }

    public function count(mixed $locator): int
    {
        $sel = is_array($locator) ? ($locator['selector'] ?? '') : '';
        return $this->locatorCounts[$sel] ?? 1;
    }

    public function nth(mixed $locator, int $index): mixed
    {
        return ['selector' => ($locator['selector'] ?? '') . ":nth({$index})"];
    }

    public function first(mixed $locator): mixed
    {
        return $locator;
    }

    public function scrollIntoView(mixed $locator): void
    {
        $this->logs[] = ['scrollIntoView', $locator];
    }

    public function isVisible(mixed $locator): bool
    {
        return true;
    }

    public function isEnabled(mixed $locator): bool
    {
        return true;
    }

    public function evaluate(mixed $context, string $expression, mixed $arg = null): mixed
    {
        $this->logs[] = ['evaluate', $expression, $arg];
        return $this->evalResults[$expression] ?? true;
    }

    public function screenshot(mixed $pageOrLocator, array $options = []): string
    {
        $this->logs[] = ['screenshot', $options];
        return $options['path'] ?? 'mock_screenshot.png';
    }

    public function waitForSelector(mixed $context, string $selector, array $options = []): void
    {
        $this->logs[] = ['waitForSelector', $selector, $options];
    }

    public function frameLocator(mixed $context, string $selector): mixed
    {
        $this->logs[] = ['frameLocator', $selector];
        return ['frame' => $selector];
    }
}

class StepWrightTest extends TestCase
{
    public function testEnums(): void
    {
        $this->assertEquals('id', SelectorType::Id->value);
        $this->assertEquals('text', DataType::Text->value);
        $this->assertEquals('navigate', ActionType::Navigate->value);
    }

    public function testHelpersPlaceholders(): void
    {
        $replacedIndex = Helpers::replaceIndexPlaceholders('item_{{ i }}_{{ i_plus1 }}', 2);
        $this->assertEquals('item_2_3', $replacedIndex);

        $replacedData = Helpers::replaceDataPlaceholders('hello {{ name }}', ['name' => 'World']);
        $this->assertEquals('hello World', $replacedData);
    }

    public function testFlattenNestedForeachResults(): void
    {
        $nestedData = [
            'category' => 'Books',
            'item_0' => [
                ['title' => 'Book A'],
                ['title' => 'Book B']
            ]
        ];

        $flattened = Helpers::flattenNestedForeachResults($nestedData);
        $this->assertCount(2, $flattened);
        $this->assertEquals('Books', $flattened[0]['category']);
        $this->assertEquals('Book A', $flattened[0]['title']);
    }

    public function testBaseStepDTOFromArray(): void
    {
        $stepArray = [
            'id' => 'test_step',
            'action' => 'click',
            'object' => '.btn',
            'object_type' => 'class',
            'subSteps' => [
                ['id' => 'sub1', 'action' => 'data', 'key' => 'title']
            ]
        ];

        $step = BaseStep::fromArray($stepArray);
        $this->assertEquals('test_step', $step->id);
        $this->assertEquals('click', $step->action);
        $this->assertEquals('class', $step->objectType);
        $this->assertCount(1, $step->subSteps);
        $this->assertEquals('sub1', $step->subSteps[0]->id);
    }

    public function testStepExecutorWithMockDriver(): void
    {
        $driver = new MockDriver();

        $step = new BaseStep(
            id: 'nav',
            action: 'navigate',
            value: 'https://example.com'
        );

        $collector = [];
        StepExecutor::executeStep($driver, 'mock_page', $step, $collector);

        $this->assertCount(1, $driver->logs);
        $this->assertEquals('goto', $driver->logs[0][0]);
        $this->assertEquals('https://example.com', $driver->logs[0][1]);
    }

    public function testForeachLoopExecution(): void
    {
        $driver = new MockDriver();
        $driver->locatorCounts['.item'] = 2;
        $driver->textContents['.item:nth(0)'] = 'Item 1';
        $driver->textContents['.item:nth(1)'] = 'Item 2';

        $template = new TabTemplate(
            tab: 'loop_tab',
            steps: [
                new BaseStep(
                    id: 'loop',
                    action: 'foreach',
                    object: '.item',
                    subSteps: [
                        new BaseStep(id: 'txt', action: 'data', key: 'title')
                    ]
                )
            ]
        );

        $results = StepWright::runScraper([$template], new RunOptions(browser: ['headless' => true]), $driver);
        $this->assertCount(2, $results);
    }

    public function testParameterizedTemplate(): void
    {
        $driver = new MockDriver();

        $baseTemplate = new TabTemplate(
            tab: 'search_{{keyword}}',
            steps: [
                new BaseStep(id: 'nav', action: 'navigate', value: 'https://example.com?q={{keyword}}'),
                new BaseStep(id: 'title', action: 'getTitle', key: 'title')
            ]
        );

        $paramTemplate = new ParameterizedTemplate(
            template: $baseTemplate,
            parameterKey: 'keyword',
            values: ['php', 'playwright']
        );

        $results = StepWright::runScraper([$paramTemplate], new RunOptions(browser: ['headless' => true]), $driver);
        $this->assertCount(2, $results);
    }

    public function testReadWriteDataHandlers(): void
    {
        $driver = new MockDriver();
        $tmpFile = sys_get_temp_dir() . '/stepwright_test_' . uniqid() . '.json';

        $collector = ['items' => [['a' => 1], ['a' => 2]]];

        // Write step
        $writeStep = new BaseStep(id: 'write', action: 'writeData', value: $tmpFile, key: 'items', dataType: 'json');
        StepExecutor::executeStep($driver, 'mock_page', $writeStep, $collector);

        $this->assertFileExists($tmpFile);

        // Read step
        $readCollector = [];
        $readStep = new BaseStep(id: 'read', action: 'readData', value: $tmpFile, key: 'loaded', dataType: 'json');
        StepExecutor::executeStep($driver, 'mock_page', $readStep, $readCollector);

        $this->assertEquals([['a' => 1], ['a' => 2]], $readCollector['loaded']);

        @unlink($tmpFile);
    }

    public function testCustomCallbackStep(): void
    {
        $driver = new MockDriver();
        $collector = [];

        $customStep = new BaseStep(
            id: 'custom_action',
            action: 'custom',
            key: 'custom_result',
            callback: function ($page, $collector, $step) {
                return 'Custom Value';
            }
        );

        StepExecutor::executeStep($driver, 'mock_page', $customStep, $collector);
        $this->assertEquals('Custom Value', $collector['custom_result']);
    }

    public function testPlaywrightDriverInstantiation(): void
    {
        $driver = new \Lablnet\StepWright\Drivers\PlaywrightDriver();
        $this->assertInstanceOf(\Lablnet\StepWright\Contracts\BrowserDriverInterface::class, $driver);
    }

    public function testPaginationMaxPages(): void
    {
        $driver = new MockDriver();
        $template = new TabTemplate(
            tab: 'pagination_test',
            steps: [
                new BaseStep(id: 'nav', action: 'navigate', object: 'https://example.com'),
                new BaseStep(id: 'title', action: 'getTitle', key: 'title'),
            ],
            pagination: new PaginationConfig(
                maxPages: 2,
                paginationFirst: false,
                nextButton: new NextButtonConfig(objectType: 'css', object: '.next')
            )
        );

        $results = StepWright::runScraper([$template], new RunOptions(browser: ['headless' => true]), $driver);
        $this->assertCount(2, $results);
    }
}
