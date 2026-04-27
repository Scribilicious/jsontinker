# JsonTinker

A PHP-based browser UI for editing JSON files. Drop it behind any PHP web server,
point it at a `data/` directory full of `.json` files, and edit them through
type-aware dynamic forms — strings become textareas, numbers become number
inputs, booleans become checkboxes, nested objects and arrays become collapsible
sections with add/remove/reindex.

Built as a plain single-page app with no framework, no Composer, no build step.

## Quick Start

When hosting JsonTinker don't forget to give the data folder access rights:
```bash
cd jsontinker && chmod 707 data
```

Run JsonTinker:
```bash
cd jsontinker && php -S localhost:8000
```

Open `http://localhost:8000` in a browser. All `.json` files in the `data/`
directory appear in the sidebar.

## Project Structure

```
jsontinker/
├── index.php            # Entry point — routing, auth, form handling, HTML
├── libs/
│   ├── config.php       # $config array (title, favicon, version, auth keys)
│   ├── JsonFile.php     # Read/write/validate JSON files
│   └── Helper.php       # Dot-notation form processing + recursive rendering
├── styles/
│   └── app.css          # All styling
├── js/
│   └── app.js           # Auto-expand textareas, sidebar toggle, array add/remove/reindex
└── data/                # JSON files to edit
    ├── galaxies.json
    ├── planets_info.json
    ├── ...
```

## How It Works

1. A JSON file is selected from the sidebar, read via `JsonFile::read()`
2. `Helper::renderFormFields()` recursively walks the data and renders HTML
   inputs with dot-notation names (e.g. `data[player.0.name]`)
3. On submit, `Helper::processFormData()` splits the dot-notation keys back
   into a nested PHP array
4. `JsonFile::validate()` and `JsonFile::write()` save with `JSON_PRETTY_PRINT`

## Configuration

Edit `libs/config.php`:

```php
$config = [
    'favicon' => '🚀',
    'title'   => 'JsonTinker',
    'description' => 'JSON file editor.',
    'version' => '1.0.0',
    'visible' => false,
    'keys'    => [],
];
```

| Key | Purpose |
|-----|---------|
| `favicon` | Emoji used as page favicon |
| `title` | Browser tab title |
| `description` | Meta description |
| `version` | Displayed in footer |
| `visible` | `false` = `noindex, nofollow`; `true` = `index, follow` |
| `keys` | Optional. Array of SHA-256 hashes for access control |

## Auth

`keys` is **optional** — leave it as an empty array `[]` and the editor is
accessible to everyone with no login screen.

If `keys` contains one or more SHA-256 hashes, a login screen is shown. You can
authenticate in two ways:

### 1. Key query parameter

```
http://localhost:8000/?k=my-secret-key
```

The key is hashed with SHA-256 and compared against the stored hashes.

### 2. Username + password form

The hash is computed as `sha256(username . password)` — the username and
password are concatenated directly with **no separator**.

### Generating a key hash

```bash
echo -n "my-secret-key" | shasum -a 256
```

For the username/password form:

```bash
echo -n "myusermypass" | shasum -a 256
```

Paste the output (the long hex string) into the `keys` array in `config.php`.

## Caveats

- **Type coercion**: On save, numeric strings with `.` become floats, without
  `.` become ints, `"true"`/`"false"`/`"null"` (case-insensitive) become
  booleans/null. You cannot store those literal strings through the editor.
- **Array remove reindexes**: Removing an array item reindexes all remaining
  items to avoid gaps. Remove item 2 of 5, indices 0-3 are submitted.
- **`$config` is global**: `config.php` defines `$config` in the global scope
  and is `require`'d (not returned), so it's used directly as a global.
