# Selectors & Data Extraction

StepWright PHP supports multiple selector types to locate elements and extract data.

## Selector Types (`SelectorType` Enum)

- `css` / `SelectorType::Css`: CSS selector string (e.g., `.quote > .text`, `h1`).
- `xpath` / `SelectorType::Xpath`: XPath expression (e.g., `//div[@class="quote"]`).
- `text` / `SelectorType::Text`: Text content match.
- `tag` / `SelectorType::Tag`: Element tag name.
- `id` / `SelectorType::Id`: Element ID attribute.
- `class` / `SelectorType::Class`: Element CSS class name.

## Extracting Data Steps

### Text Content (`data` / `getText`)

```php
new BaseStep(
    id: 'quote_text',
    action: 'data',
    objectType: 'css',
    object: '.quote .text',
    key: 'quote',
    dataType: 'text'
)
```

### Attribute Extraction (`attr` / `getAttribute`)

Extract specific HTML attributes like `href` or `src`:

```php
new BaseStep(
    id: 'author_link',
    action: 'attr',
    objectType: 'css',
    object: '.quote span a',
    value: 'href',
    key: 'author_url'
)
```

### Loop Action (`loop` / `foreach`)

Iterate over dynamic lists of DOM elements:

```php
new BaseStep(
    id: 'quote_loop',
    action: 'loop',
    objectType: 'css',
    object: '.quote',
    subSteps: [
        new BaseStep(id: 'text', action: 'data', objectType: 'css', object: '.text', key: 'text'),
        new BaseStep(id: 'author', action: 'data', objectType: 'css', object: '.author', key: 'author'),
    ]
)
```
