# Upgrading from v1 to v2

## Requirements

- PHP 8.4+
- Laravel 12 or 13
- Livewire 4+
- TallStackUI 3+

## Breaking Changes

### Removed: `syncFromAlpine()`

The internal JS-bridge method `syncFromAlpine()` has been removed. This was only used internally by the Alpine component to sync state back to Livewire. If you called this method directly, use `$wire.set()` or `$wire.$refresh()` instead.

### Removed: `SupportsCache` trait

The `SupportsCache` trait has been absorbed into `StoresSettings`. If you referenced `SupportsCache` directly in your code, replace with `StoresSettings`. The `getCacheKey()` method is now available on `StoresSettings`.

### JavaScript

The `data_table()` Alpine component has been reduced from ~1,435 lines to ~50 lines. It now handles only DOM interactions (sticky columns). All data rendering is done server-side via Blade.

If you extended `data_table()` with custom JavaScript, move that logic to PHP (Livewire component methods or traits).

The `formatters.js` file has been removed. All formatting is now handled server-side via the `FormatterRegistry`.

### Listener Renamed

The Livewire listener has been renamed:

| v1 | v2 |
|----|-----|
| `loadData` | `dataTableReload` → `reloadData` |

If you dispatched `loadData` events to the datatable from other components, update to `dataTableReload`.

### Removed: `#[Renderless]` from data-loading methods

In v1, methods like `loadData()`, `gotoPage()`, `sortTable()`, `startSearch()` were marked `#[Renderless]` because Alpine.js rendered data client-side. In v2, all rendering is server-side via Blade. These methods **must not** have `#[Renderless]` — otherwise the component won't re-render with the loaded data.

**If you override `loadData()` in your Livewire component and add `#[Renderless]`, the table will appear empty.** Remove the attribute.

### Events

| v1 Event | v2 Status |
|----------|-----------|
| `data-table-data-loaded` | Removed (Blade renders directly from `$this->data`) |
| `syncFromAlpine` | Removed |

### `$page` Property Type

The `$page` property changed from `int|string` to `int`. If you set this property as a string, update to an integer.

### Visibility Changes

All `private` methods in traits have been changed to `protected` to allow extension in package consumers. This is not a breaking change for existing code, but if you relied on the private visibility for some reason, be aware.

### Published Views

If you published views, re-publish them. The view structure has changed.

## Non-Breaking Changes

Everything below is additive and does not require changes to existing code.

### Server-side Formatters

v2 adds a `FormatterRegistry` for server-side value formatting. Casts implementing `HasFrontendFormatter` are auto-detected. You can also register custom formatters:

```php
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;
use TeamNiftyGmbH\DataTable\Formatters\MoneyFormatter;

app(FormatterRegistry::class)->register(MyCast::class, new MoneyFormatter());
```

### New Lazy Components

- `DataTableFilters` — filter management (loads on sidebar open)
- `DataTableOptions` — column/aggregation/grouping options (loads on sidebar open)

### New Method: `reloadData()`

Call `$this->reloadData()` to trigger a data refresh without a full component re-render.

### Public API Compatibility

All public properties and methods from v1 are preserved in v2. The `DataTable` base class API is backwards-compatible.
