# Step Actions Reference

Comprehensive reference of all step actions supported by StepWright PHP (`ActionType` Enum).

## Interaction Actions

- **`navigate` / `goto`**: Navigate browser tab to `$step->object`.
- **`click`**: Click target element specified by `$step->object`.
- **`dblclick`**: Double click target element.
- **`check`**: Check a checkbox or radio input element.
- **`fill`**: Fill an input field with `$step->value`.
- **`type`**: Type keystrokes into an input field.
- **`clear`**: Clear text content from an input field.
- **`hover`**: Hover cursor over element.
- **`select` / `selectOption`**: Select option from `<select>` element.

---

## Data Extraction Actions

- **`data` / `getText`**: Extract text content from element and store in `$step->key`.
- **`attr` / `getAttribute`**: Extract attribute value (specified by `$step->value`) and store in `$step->key`.
- **`getTitle`**: Obtain current page document title.
- **`getUrl`**: Obtain current page URL.
- **`evaluate`**: Execute JavaScript expression on page and return result.

---

## Control Flow & Logic Actions

- **`loop` / `foreach`**: Iterate over all matching elements for selector `$step->object` and execute `$step->subSteps` for each item.
- **`custom`**: Execute PHP closure passed in `$step->callback($page, $collector, $step)`.
- **`writeData`**: Save current collected data array to file path `$step->value` (e.g. JSON format).
- **`readData`**: Read data from file path `$step->value` into collector key `$step->key`.
