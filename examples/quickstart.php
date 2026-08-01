<?php

require __DIR__ . '/../vendor/autoload.php';

use Lablnet\StepWright\StepWright;
use Lablnet\StepWright\DTOs\TabTemplate;
use Lablnet\StepWright\DTOs\BaseStep;
use Lablnet\StepWright\DTOs\RunOptions;

$flow = new TabTemplate(
    tab: 'hackernews',
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

$options = new RunOptions(
    onResult: function ($result, $index) {
        echo "Extracted item #{$index}: " . json_encode($result) . "\n";
    }
);

echo "Starting scraper...\n";
$results = StepWright::runScraper([$flow], $options);
echo "Done! Collected " . count($results) . " items.\n";
