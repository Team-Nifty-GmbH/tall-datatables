# Upgrading from v1 to v2

## Requirements

- PHP 8.2+
- Laravel 12+
- Livewire 4+
- TallStackUI 2+

## Breaking Changes

### JavaScript

The v1 Alpine.js state management (1,435 lines) has been replaced by server-side Blade rendering. The `data_table()` Alpine component is now minimal (~50 lines) and handles only DOM interactions (sticky columns).

If you had custom JavaScript extending `data_table()`, you will need to move that logic to PHP (Livewire component methods or traits).

The `formatters.js` file has been removed. All formatting is now handled server-side via the `FormatterRegistry`.

### Events

| v1 Event | v2 Replacement |
|----------|----------------|
| `data-table-data-loaded` | Removed (Blade renders directly from `$this->data`) |
| `syncFromAlpine` | Removed |

### Formatters

v1 used `HasFrontendAttributes` trait on Cast classes to define JS formatters. v2 uses server-side `Formatter` classes registered via `FormatterRegistry`:

```php
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;
use TeamNiftyGmbH\DataTable\Formatters\MoneyFormatter;

// In a service provider:
app(FormatterRegistry::class)->register(MyCast::class, new MoneyFormatter());
```

### Component API

- `getConfig()` — Deprecated, no longer called by views
- `syncFromAlpine()` — Removed
- `forceRender()` — Deprecated
- `hydrate()` — No longer skips render

### New Components

- `DataTableFilters` — Lazy Livewire component for filter management
- `DataTableOptions` — Lazy Livewire component for column/aggregation/grouping options

### Views

If you published views, you will need to re-publish them. The view structure has changed significantly.
