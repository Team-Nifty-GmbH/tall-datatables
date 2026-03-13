# tall-datatables v2 Rewrite Design

## Motivation

The v1 architecture relies on ~1,435 lines of JavaScript (Alpine.js) for state management, value formatting, filter parsing, and HTML generation. This creates:

- Fragile state synchronization between Alpine and Livewire (`syncFromAlpine`, `_directData`, `_wireVersion`)
- Bugs caused by event timing (e.g., aggregates dispatched before calculation)
- Hard-to-debug dual filter systems (text + UI must stay in sync)
- 502 lines of client-side formatters duplicating server-side knowledge

Livewire v4 makes most of this JS unnecessary. The rewrite moves logic to PHP, keeps the public PHP API stable, and reduces JS to ~100-200 lines for DOM-only interactions.

## Goals

- Same feature set as v1 (no features added or removed)
- Public PHP API stays compatible (minimal breaking changes)
- JS reduced to DOM-only concerns (drag & drop, sticky scroll, keyboard shortcuts)
- Each component and class is isolated, testable, and under 400 lines
- Lazy-load everything not immediately visible

## Non-Goals

- No new features (inline editing, virtual scrolling, etc.)
- No Livewire v3 support (v2 requires Livewire v4+)
- No backwards compatibility for custom JS built on `data-table.js` or `formatters.js`

---

## Component Architecture

```
DataTable (main Livewire component)
├── Renders: table, rows, cells, footer, pagination
├── Owns: query building, data loading, sorting, pagination, selection
├── State: data, page, perPage, search, selected, enabledCols, aggregates
│
├── DataTableFilters (lazy Livewire component, in sidebar)
│   ├── Filter UI builder (column, operator, value)
│   ├── Text filter parsing
│   ├── Saved filters (load/save/delete)
│   └── Communicates via dispatch('filters-changed', filters: [...])
│
├── DataTableOptions (lazy Livewire component, in sidebar)
│   ├── Column show/hide + reorder
│   ├── Aggregation config (Sum/Avg/Min/Max)
│   ├── Grouping config
│   ├── Export config
│   └── Communicates via dispatch('options-changed', ...)
│
└── Rows (Blade partials, NOT Livewire components)
    └── Server-side formatting in RowTransformer
```

### Key Decisions

- **Rows are NOT Livewire components.** 15+ components per page is too expensive. For broadcasts, the main component handles `refreshRow($id)` to re-render a single row without reloading all data.
- **Filters and Options are lazy.** They load only when the user opens the sidebar.
- **Communication is one-directional.** Child components dispatch events upward. DataTable listens with `#[On(...)]` handlers. No bi-directional sync.
- **Search uses `wire:model.live.debounce.300ms`** directly on the input. `updatedSearch()` lifecycle hook triggers `loadData()`. No Alpine watcher.

---

## State Management

### v1 (eliminated)

```
Alpine State ←syncFromAlpine→ Livewire State
    ↓ $watch                        ↓ dispatch event
Alpine _directData ← data-table-data-loaded ← setData()
```

Three sync mechanisms, race conditions, timing bugs.

### v2

```
DataTable (Livewire)
    ├── Own state: data, page, search, selected, aggregates
    ├── Blade renders directly from $this->data
    │
    ├── ← dispatch('filters-changed') ← DataTableFilters (lazy)
    ├── ← dispatch('options-changed') ← DataTableOptions (lazy)
    │
    └── Alpine only for:
        ├── Sidebar open/close (x-show)
        ├── Drag & Drop column reorder
        ├── Sticky scroll positioning
        └── Keyboard shortcuts
```

### Eliminated

- `syncFromAlpine()` — gone, Livewire properties directly
- `_directData` / `_dataVersion` / `_wireVersion` — gone, no cache layer needed
- `data-table-data-loaded` event — gone, Blade renders from `$this->data`
- `$wire.$watch()` chains — gone, Livewire lifecycle hooks (`updated*`) instead
- Alpine getter/setter pairs — gone entirely

---

## Server-Side Formatting

### Approach

Each Cast implementing `HasFrontendFormatter` gets a `formatForFrontend()` method:

```php
interface HasFrontendFormatter
{
    public function formatForFrontend(mixed $value, array $context = []): string;
}
```

### Data Structure Per Cell

```php
['raw' => 150.00, 'display' => '150,00 €']
```

- `raw` — for filtering, sorting, aggregation
- `display` — pre-formatted HTML string for rendering

### RowTransformer

Central class that converts a Model into the `raw + display` array:

```php
class RowTransformer
{
    public function transform(Model $model, array $enabledCols, FormatterRegistry $registry): array
    {
        // For each enabled column:
        // 1. Get raw value
        // 2. Look up formatter from registry
        // 3. Format for display
        // 4. Return ['raw' => ..., 'display' => ...]
    }
}
```

### FormatterRegistry

Maps Cast classes to Formatter instances. Extensible for custom casts:

```php
$registry->register(Money::class, new MoneyFormatter());
$registry->register(Percentage::class, new PercentageFormatter());
```

Auto-detection for unregistered types (string, int, float, bool, date, datetime).

### Result

- `formatters.js` (502 lines) eliminated entirely
- Aggregation footer also formatted server-side
- Blade renders `{!! $cell['display'] !!}` or simple `{{ $cell['raw'] }}`

---

## Blade Views Structure

No file over 200 lines. No inline Alpine logic beyond `wire:click`, `wire:model`, `x-show`, `x-on:click`.

```
views/
├── livewire/
│   ├── data-table.blade.php          — Main component (slim)
│   ├── filters.blade.php             — DataTableFilters view
│   └── options.blade.php             — DataTableOptions view
│
├── components/
│   ├── table.blade.php               — Table with header + body + footer
│   ├── row.blade.php                 — Single row
│   ├── cell.blade.php                — Single cell (renders display value)
│   ├── head.blade.php                — Search, actions, title
│   ├── pagination.blade.php          — Pagination controls
│   ├── footer.blade.php              — Aggregation rows
│   ├── grid.blade.php                — Grid/card layout alternative
│   └── sidebar.blade.php             — Sidebar wrapper
│
├── filters/
│   ├── builder.blade.php             — Filter UI builder
│   ├── saved.blade.php               — Saved filters list
│   └── badge.blade.php               — Filter badge
│
└── options/
    ├── columns.blade.php             — Column show/hide
    ├── aggregation.blade.php         — Aggregation checkboxes
    ├── grouping.blade.php            — Grouping config
    └── export.blade.php              — Export config
```

Grouped rows as Blade partial instead of JS HTML string generation.

---

## PHP Class Structure

```
src/
├── DataTable.php                      — Main component (~400 lines)
│   ├── mount(), render(), loadData()
│   ├── Event listeners (#[On(...)])
│   └── Customization hooks (getBuilder, getLayout, etc.)
│
├── Components/
│   ├── DataTableFilters.php           — Lazy Livewire component
│   └── DataTableOptions.php           — Lazy Livewire component
│
├── Traits/
│   ├── BuildsQueries.php             — buildSearch(), applyFilters(), addFilter()
│   ├── SupportsAggregation.php       — getAggregate(), aggregatable config
│   ├── SupportsGrouping.php          — loadGroupedData(), group pagination
│   ├── SupportsRelations.php         — Relation loading & filtering
│   ├── SupportsSelecting.php         — Row selection, wildcard, bulk
│   ├── SupportsExporting.php         — Excel export
│   └── StoresSettings.php            — User settings persistence
│
├── Formatters/
│   ├── FormatterRegistry.php         — Registers Cast → Formatter mapping
│   ├── Contracts/
│   │   └── Formatter.php             — interface: format(mixed $value, array $context): string
│   ├── MoneyFormatter.php
│   ├── DateFormatter.php
│   ├── BooleanFormatter.php
│   ├── BadgeFormatter.php
│   ├── LinkFormatter.php
│   ├── ImageFormatter.php
│   ├── PercentageFormatter.php
│   └── ArrayFormatter.php
│
├── Filters/
│   ├── FilterParser.php              — Text input → filter array
│   └── FilterApplier.php             — Filter array → Eloquent WHERE clauses
│
└── Support/
    ├── RowTransformer.php            — Model → array with raw + display values
    ├── ColumnResolver.php            — Column discovery, labels, types
    └── SessionFilter.php             — Session filter persistence
```

### Key Changes

- **DataTable.php shrinks from 1,186 to ~400 lines** — query building, filter parsing, row transformation in own classes
- **SupportsCache eliminated** — `_directData` caching no longer needed
- **Filter logic split** — parser (text → structure) and applier (structure → WHERE) separated
- **Formatters as own classes** — registrable via `FormatterRegistry`, not embedded in Casts
- **RowTransformer** — single place for Model → `['raw' => ..., 'display' => ...]`

---

## Public API (Stays Compatible)

```php
class OrderDataTable extends DataTable
{
    protected string $model = Order::class;

    public array $enabledCols = ['order_number', 'contact.name', 'total_net', 'created_at'];

    public array $aggregatable = ['total_net', 'total_gross'];

    public bool $isSelectable = true;

    protected function getBuilder(): Builder
    {
        return Order::query()->where('is_active', true);
    }

    protected function getLayout(): string
    {
        return 'tall-datatables::components.table';
    }

    protected function getRowAttributes(): ComponentAttributeBag { ... }
    protected function getCellAttributes(): ComponentAttributeBag { ... }
    protected function getComponentAttributes(): ComponentAttributeBag { ... }
    protected function getAppends(): array { ... }
}
```

---

## Breaking Changes

### What breaks

- **JS files removed:** `data-table.js` and `formatters.js` no longer exist. Custom JS built on these must be rewritten.
- **Events changed:** `data-table-data-loaded` no longer dispatched. Inter-component events have new names.
- **HasFrontendFormatter interface:** Must implement `formatForFrontend()` instead of just returning a formatter name.
- **Blade view namespaces:** Internal view paths may change.

### What stays

- DataTable class definition: `$model`, `$enabledCols`, `$aggregatable`, `$isSelectable`, etc.
- Override hooks: `getBuilder()`, `getLayout()`, `getAppends()`, `getCellAttributes()`, `getRowAttributes()`
- `InteractsWithDataTables` interface on models
- `HasDatatableUserSettings` trait on User model
- `DatatableUserSetting` model + migration
- Search route / SearchController
- Config file

---

## Migration Strategy

- **2.x branch** parallel to 1.x
- **1.x** continues to receive bugfixes
- **2.0-alpha → 2.0-beta → 2.0** on 2.x branch
- **UPGRADE.md** documents all breaking changes with migration examples
- Existing casts (`Money`, `Percentage`, `BcFloat`, `Image`, `Link`) migrated as part of the rewrite
- Composer constraints: `"livewire/livewire": "^4.0"`, `"tallstackui/tallstackui": "^3.0"`

---

## Remaining JS (~100-200 lines)

Only DOM interactions that genuinely require client-side code:

- Sidebar open/close toggle (`x-show`)
- Drag & drop column reorder
- Sticky column scroll positioning
- Keyboard shortcuts (arrow keys for navigation)
- `x-cloak`, `x-show`, `x-on:click` for UI toggles

No state management. No data caching. No formatters. No HTML generation.
