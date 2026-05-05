# JSON Editor

Project Name: JsonTinker

A PHP-based dynamic JSON editor. Provides a browser UI to edit any JSON file by rendering it as dynamic forms with type-aware inputs (strings → textareas, numbers → number inputs, booleans → checkboxes, nested objects/arrays → collapsible sections with add/remove).

## Architecture

Plain PHP single-page app (no framework, no Composer). Layout: sidebar lists all `.json` files in `data/`, editor area renders dynamic form fields for the selected file's contents.

```
jsontinker/
├── index.php          # Entry point — routing, auth, form handling, HTML output
├── libs/
|   ├── composer.json
│   ├── config.php     # $config array (title, version, auth keys)
│   ├── JsonFile.php   # Class: read/write/validate JSON files
│   └── Helper.php     # Class: dot-notation form processing + recursive field rendering
├── styles/
│   └── app.css        # All styling (no CSS framework)
├── js/
│   └── app.js         # Client-side: auto-expand textareas, sidebar toggle, collapsible sections, array add/remove/reindex
└── data/              # Game data JSON files — the files this editor manages
    ├── people.json
    └── test.json
```

**No build step, no package manager, no tests.** Serve from any PHP-capable web server.

## How It Works

### Form Submission Flow (index.php)

1. Read selected JSON file via `JsonFile::read()`
2. On POST with `data` array: `Helper::processFormData($newData)` converts flat PHP `$_POST` keys into nested structure using dot-notation key splitting
3. `JsonFile::validate()` checks data is array + valid JSON
4. `JsonFile::write()` saves back with `JSON_PRETTY_PRINT`
5. Displays success/error message

### Dot-Notation Key System (Helper.php)

This is the core architectural pattern — form inputs use dot-notation names that `processFormData()` parses into nested arrays:

- `name` → `["name" => value]`
- `player.0.name` → `["player" => [["name" => value]]]`
- `player[0].name` → same (array index in brackets)
- `player.0.addresses.0.city` → deeply nested

The input name attribute format in PHP: `data[player.0.name]` → `$_POST['data']['player.0.name']`

### Form Rendering (Helper.php::renderFormFields)

Recursively walks JSON data and renders the appropriate input type:

| JSON type      | HTML input                                                    | Notes                                |
| -------------- | ------------------------------------------------------------- | ------------------------------------ |
| string/null    | `<textarea>`                                                  | monospace font, auto-expanding (JS)  |
| int/float      | `<input type="number" step="any">`                            |                                      |
| bool           | hidden input `false` + `<input type="checkbox" value="true">` | Checked/unchecked maps to true/false |
| object (assoc) | nested section with collapsible header                        |                                      |
| array (list)   | array container with add/remove buttons, reindex on change    |                                      |

## Non-Obvious Patterns & Gotchas

- **No `$config` import**: `init.php` defines `$config` in the global scope. It is `require`'d, not returned. `$config` is used directly as a global.
- **Auth is security-through-obscurity**: `$config['keys']` contains SHA-256 hashes of `username+password` (no delimiter). Empty `keys` = no auth. `visible: false` sets `noindex, nofollow`.
- **ProcessFormData type coercion**: String values are auto-converted: numerics with `.` → float, without `.` → int, `"true"`/`"false"`/`"null"` (case-insensitive) → bool/null. This means you can't store those strings as-is through the editor.
- **Array reindexing after remove**: The JS `removeArrayItem` function removes the DOM element, then calls `reindexArray` to reassign indices and update all input names/IDs. **Crucially**, this also reassigns the last item's index to the removed position — so if you remove item 2 of 5, the form submits indices 0-3, not 0,1,3,4. This is intentional to avoid gaps.
- **`addArrayItem()` cloning vs creation**: If the array has existing items in the DOM, it clones the first item's structure. Otherwise it creates a default textarea item. This means dynamic arrays always follow the shape of the first element.
- **Section collapse via CSS class**: Clicking a `.section-header` toggles `.collapsed` on its `.nested-section` parent. CSS hides `.nested-content` within `.collapsed` sections. Clicking buttons inside the header doesn't trigger collapse (`event.target.closest('button')` guard).
- **Subtitle has `padding-right: 90px`** on mobile (480px breakpoint) to keep text visible behind the absolute-positioned `.menu-toggle` button.

## Running

Serve the `jsontinker/` directory with any PHP-capable web server:

```bash
cd jsontinker && php -S localhost:8000
```
