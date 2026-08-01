# Error Handling & Waits

Web automation often deals with asynchronous network calls, dynamic DOM updates, and transient errors.

## Execution Wait Options

Use `wait` on any `BaseStep` to pause execution for a specified duration in milliseconds:

```php
new BaseStep(
    id: 'wait_step',
    action: 'waitForTimeout',
    value: '2000' // Wait 2 seconds
)
```

Wait explicitly for DOM load states:

```php
new BaseStep(
    id: 'wait_network',
    action: 'waitForLoadState',
    value: 'networkidle' // Wait until network is idle
)
```

## Error Handling Controls

### Ignore Errors (`ignoreError`)

Setting `ignoreError: true` allows the scraper to log warnings and continue step execution if a non-fatal element lookup or click fails:

```php
new BaseStep(
    id: 'optional_banner_close',
    action: 'click',
    objectType: 'css',
    object: '#cookie-accept',
    ignoreError: true
)
```

### Terminate on Error (`terminateOnError`)

Setting `terminateOnError: true` halts the workflow immediately if an essential step fails:

```php
new BaseStep(
    id: 'critical_login',
    action: 'click',
    objectType: 'id',
    object: 'submit-login',
    terminateOnError: true
)
```
