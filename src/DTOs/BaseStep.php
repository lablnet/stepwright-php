<?php

declare(strict_types=1);

namespace Lablnet\StepWright\DTOs;

use Lablnet\StepWright\Enums\ActionType;

class BaseStep
{
    /** @var BaseStep[]|null */
    public ?array $subSteps = null;

    public function __construct(
        public string $id,
        public ?string $description = null,
        public string|null $objectType = null,
        public ?string $object = null,
        public string $action = 'navigate',
        public mixed $value = null,
        public ?string $key = null,
        public string|null $dataType = null,
        public ?int $wait = null,
        public ?bool $terminateOnError = null,
        ?array $subSteps = null,
        public ?bool $autoScroll = null,
        public ?string $indexKey = null,
        public mixed $callback = null,
        public ?string $frameSelector = null,
        public string|null $frameSelectorType = null,
        public ?int $virtualScrollOffset = null,
        public ?int $virtualScrollDelay = null,
        public ?string $virtualScrollUniqueKey = null,
        public ?int $virtualScrollLimit = null,
        public ?string $virtualScrollContainer = null,
        public string|null $virtualScrollContainerType = null,
        public ?string $targetObject = null,
        public string|null $targetObjectType = null,
        public ?int $retry = null,
        public ?int $retryDelay = null,
        public ?string $skipIf = null,
        public ?string $onlyIf = null,
        public ?string $waitForSelector = null,
        public ?int $waitForSelectorTimeout = null,
        public ?string $waitForSelectorState = null,
        public ?array $fallbackSelectors = null,
        public ?array $clickModifiers = null,
        public ?bool $doubleClick = null,
        public ?bool $forceClick = null,
        public ?bool $rightClick = null,
        public ?bool $clearBeforeInput = null,
        public ?int $inputDelay = null,
        public ?bool $required = null,
        public ?string $defaultValue = null,
        public ?string $regex = null,
        public ?int $regexGroup = null,
        public ?string $transform = null,
        public ?int $timeout = null,
        public ?string $waitUntil = null,
        public ?array $randomDelay = null,
        public ?bool $requireVisible = null,
        public ?bool $requireEnabled = null,
        public ?bool $skipOnError = null,
        public ?bool $continueOnEmpty = null
    ) {
        $this->subSteps = $subSteps;
    }

    public static function fromArray(array $data): self
    {
        $subSteps = null;
        if (isset($data['subSteps']) && is_array($data['subSteps'])) {
            $subSteps = array_map(
                fn($stepData) => $stepData instanceof self ? $stepData : self::fromArray($stepData),
                $data['subSteps']
            );
        }

        return new self(
            id: (string) ($data['id'] ?? uniqid('step_')),
            description: $data['description'] ?? null,
            objectType: $data['object_type'] ?? $data['objectType'] ?? null,
            object: $data['object'] ?? null,
            action: $data['action'] ?? 'navigate',
            value: $data['value'] ?? null,
            key: $data['key'] ?? null,
            dataType: $data['data_type'] ?? $data['dataType'] ?? null,
            wait: $data['wait'] ?? null,
            terminateOnError: $data['terminateonerror'] ?? $data['terminateOnError'] ?? null,
            subSteps: $subSteps,
            autoScroll: $data['autoScroll'] ?? null,
            indexKey: $data['index_key'] ?? $data['indexKey'] ?? null,
            callback: $data['callback'] ?? null,
            frameSelector: $data['frameSelector'] ?? null,
            frameSelectorType: $data['frameSelectorType'] ?? null,
            virtualScrollOffset: $data['virtualScrollOffset'] ?? null,
            virtualScrollDelay: $data['virtualScrollDelay'] ?? null,
            virtualScrollUniqueKey: $data['virtualScrollUniqueKey'] ?? null,
            virtualScrollLimit: $data['virtualScrollLimit'] ?? null,
            virtualScrollContainer: $data['virtualScrollContainer'] ?? null,
            virtualScrollContainerType: $data['virtualScrollContainerType'] ?? null,
            targetObject: $data['targetObject'] ?? null,
            targetObjectType: $data['targetObjectType'] ?? null,
            retry: $data['retry'] ?? null,
            retryDelay: $data['retryDelay'] ?? null,
            skipIf: $data['skipIf'] ?? null,
            onlyIf: $data['onlyIf'] ?? null,
            waitForSelector: $data['waitForSelector'] ?? null,
            waitForSelectorTimeout: $data['waitForSelectorTimeout'] ?? null,
            waitForSelectorState: $data['waitForSelectorState'] ?? null,
            fallbackSelectors: $data['fallbackSelectors'] ?? null,
            clickModifiers: $data['clickModifiers'] ?? null,
            doubleClick: $data['doubleClick'] ?? null,
            forceClick: $data['forceClick'] ?? null,
            rightClick: $data['rightClick'] ?? null,
            clearBeforeInput: $data['clearBeforeInput'] ?? null,
            inputDelay: $data['inputDelay'] ?? null,
            required: $data['required'] ?? null,
            defaultValue: $data['defaultValue'] ?? null,
            regex: $data['regex'] ?? null,
            regexGroup: $data['regexGroup'] ?? null,
            transform: $data['transform'] ?? null,
            timeout: $data['timeout'] ?? null,
            waitUntil: $data['waitUntil'] ?? null,
            randomDelay: $data['randomDelay'] ?? null,
            requireVisible: $data['requireVisible'] ?? null,
            requireEnabled: $data['requireEnabled'] ?? null,
            skipOnError: $data['skipOnError'] ?? null,
            continueOnEmpty: $data['continueOnEmpty'] ?? null
        );
    }
}
