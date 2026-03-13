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
- **Communication uses `$this->dispatch()->to(DataTable::class)`.** Since sidebar components may be teleported (e.g., `@teleport`), standard parent-child event bubbling may not work. Targeted dispatch ensures events reach the parent regardless of DOM position. DataTable listens with `#[On(...)]` handlers.
- **Search uses `wire:model.live.debounce.300ms`** directly on the input. `updatedSearch()` lifecycle hook triggers `loadData()`. No Alpine watcher.
- **Per-column text filters stay in the table header**, owned by the main DataTable component. The DataTableFilters sidebar manages the structured filter builder and saved filters. The text filter parsing (`FilterParser`) is invoked by the main component when `updatedTextFilter()` fires.

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

Standalone Formatter classes handle all formatting. Casts remain pure value objects — they do NOT implement formatting. The `HasFrontendFormatter` interface is **deprecated**; the `FormatterRegistry` replaces it entirely.

### Formatter Contract

```php
interface Formatter
{
    /**
     * @param  mixed  $value  The raw value from the model
     * @param  array  $context  Sibling attributes from the model (e.g., currency_code for money)
     * @return string  Sanitized HTML string (must call e() on any user-controlled data)
     */
    public function format(mixed $value, array $context = []): string;
}
```

The `$context` parameter receives the full model attribute array so formatters can access sibling fields (e.g., `MoneyFormatter` reads `$context['currency.iso']` for currency code, `BadgeFormatter` reads color mappings).

### XSS Protection

All formatters MUST sanitize output. The Formatter contract mandates:
- Use `e()` on any user-controlled string values before embedding in HTML
- Only produce raw HTML for known-safe structural elements (badges, icons, links with sanitized URLs)
- The `RowTransformer` validates that all `display` values are strings before passing to Blade

Blade renders with `{!! $cell['display'] !!}` for formatted output or `{{ $cell['raw'] }}` for plain values.

### Data Structure Per Cell

```php
['raw' => 150.00, 'display' => '150,00 €']
```

- `raw` — for filtering, sorting, aggregation
- `display` — pre-formatted, sanitized HTML string for rendering

### RowTransformer

Central class that converts a Model into the `raw + display` array:

```php
class RowTransformer
{
    public function transform(Model $model, array $enabledCols, FormatterRegistry $registry): array
    {
        $context = $model->attributesToArray();

        // For each enabled column:
        // 1. Get raw value (supports dot notation for relations)
        // 2. Look up formatter from registry (by Cast class or auto-detect)
        // 3. format($raw, $context) → sanitized HTML string
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

**Auto-detection** for unregistered types: inspects the Eloquent cast metadata from `$model->getCasts()`. For native PHP types (string, int, float, bool) and common cast types (date, datetime, timestamp), applies built-in formatters. Unknown types fall back to `e((string) $value)`.

### Complete Formatter List

To match v1 feature parity, the following formatters are needed:

| Formatter | v1 JS equivalent | Notes |
|-----------|-----------------|-------|
| MoneyFormatter | `money`, `coloredMoney` | Color via option flag |
| FloatFormatter | `float`, `coloredFloat` | Color via option flag |
| PercentageFormatter | `percentage`, `progressPercentage` | Progress bar via option flag |
| DateFormatter | `date`, `datetime`, `time`, `relativeTime` | Format string configurable |
| BooleanFormatter | `bool` | |
| BadgeFormatter | `state` | Maps value → color/label |
| LinkFormatter | `link`, `url`, `email`, `tel` | Type configurable |
| ImageFormatter | `image` | |
| ArrayFormatter | `array`, `object` | |
| StringFormatter | `string`, `int` | Default fallback |

### Result

- `formatters.js` (502 lines) eliminated entirely
- `HasFrontendFormatter` interface deprecated (registry-based lookup replaces it)
- Aggregation footer also formatted server-side
- Payload tradeoff: sending `raw` + `display` doubles cell data volume. For columns where `raw === display` (plain strings, integers), the RowTransformer omits `display` and Blade falls back to `{{ $cell['raw'] }}`

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

## Broadcasting / Real-Time Updates

### v1

- `HasEloquentListeners` trait manages Echo channel subscriptions in JS
- `BroadcastsEvents` trait on models dispatches events on create/update/delete
- JS listeners (`echoCreated`, `echoUpdated`, etc.) directly mutate Alpine's `_directData`

### v2

- `BroadcastsEvents` model trait stays unchanged
- `HasEloquentListeners` moves to the main DataTable component in PHP
- Echo channel subscriptions managed via `getListeners()` returning Echo event → method mapping
- `refreshRow($id)` re-queries a single model, runs it through `RowTransformer`, and replaces that row in `$this->data` — Livewire morphs only the changed row in the DOM
- `refreshData()` for create/delete events triggers a full `loadData()`. This is simpler than v1's `echoCreated` (which manually inserted rows at position 0 and adjusted pagination). The full reload is an intentional simplification — correctness over micro-optimization.
- For high-frequency broadcast scenarios, debounce rapid updates: batch multiple broadcasts within a short window into a single `loadData()` call.
- Infinite scroll (`$hasInfiniteScroll`, `loadMore()`) stays as a DataTable feature, loading additional pages into `$this->data`

---

## Performance Considerations

### Render Strategy

v1 uses `skipRender()` aggressively — most methods are `#[Renderless]`, data loads dispatch to Alpine which updates the DOM. v2 renders from `$this->data` via Blade, so every data load triggers a Livewire morph diff.

**Mitigation:**
- Livewire v4's improved morphing algorithm handles large DOM diffs efficiently
- `wire:key` on each row enables targeted morphing (only changed rows re-render)
- For actions that don't change table data (sidebar open/close, selection toggle), use `#[Renderless]` + Alpine state for zero-roundtrip interactions
- Benchmark early: measure morph diff time for 50-row / 15-column tables during alpha

### Payload Size

Sending `raw` + `display` per cell increases payload. Mitigation: `RowTransformer` omits `display` when it equals the string-cast `raw` value. Only cells with actual formatting (money, dates, badges, etc.) carry both values.

### DataTable.php Target Size

The ~400-line target is achievable with trait extraction, but may land closer to ~500 lines. The goal is "under 600 lines" as a hard limit, with traits handling query building, aggregation, grouping, selection, exporting, and settings.

---

## PHP Class Structure

```
src/
├── DataTable.php                      — Main component (~400-500 lines)
│   ├── mount(), render(), loadData()
│   ├── Event listeners (#[On(...)])
│   ├── Echo listeners (refreshRow, refreshData)
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
│   ├── SupportsSorting.php           — Row drag & drop reorder (sortRows)
│   ├── SupportsExporting.php         — Excel export
│   ├── HasEloquentListeners.php      — Echo channel subscriptions, refreshRow
│   └── StoresSettings.php            — User settings persistence
│
├── Formatters/
│   ├── FormatterRegistry.php         — Registers Cast → Formatter mapping
│   ├── Contracts/
│   │   └── Formatter.php             — interface: format(mixed $value, array $context): string
│   ├── MoneyFormatter.php
│   ├── FloatFormatter.php
│   ├── DateFormatter.php
│   ├── BooleanFormatter.php
│   ├── BadgeFormatter.php
│   ├── LinkFormatter.php
│   ├── ImageFormatter.php
│   ├── PercentageFormatter.php
│   ├── ArrayFormatter.php
│   └── StringFormatter.php
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

- **DataTable.php shrinks from 1,186 to ~400-500 lines** — query building, filter parsing, row transformation extracted to own classes
- **BuildsQueries is a new extraction** — `buildSearch()`, `applyFilters()`, `addFilter()`, `parseFilter()` extracted from DataTable.php into a trait
- **SupportsCache eliminated** — `_directData` caching no longer needed
- **Filter logic split** — parser (text → structure) and applier (structure → WHERE) separated
- **Formatters as own classes** — registrable via `FormatterRegistry`, Casts stay as pure value objects
- **RowTransformer** — single place for Model → `['raw' => ..., 'display' => ...]`
- **ColumnResolver handles type detection** — replaces v1's JS `guessType()` and `inputType()` helpers, used by filter UI for input type selection
- **SupportsSorting preserved** — row reorder stays as trait
- **HasEloquentListeners moved to PHP** — Echo subscriptions managed server-side
- **`getConfig()` eliminated** — was used to bootstrap Alpine state, no longer needed since Blade renders from `$this->data` directly
- **Row click events** — `data-table-row-clicked` moves from Alpine event to `$this->dispatch()`. `$hasNoRedirect` property stays in the public API

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
- **HasFrontendFormatter interface deprecated:** No longer used for formatting. The `FormatterRegistry` handles all formatting via standalone classes. Existing casts that implement `HasFrontendFormatter` can drop the interface.
- **Blade view paths changed:** Internal views restructured from flat to grouped (`components/`, `filters/`, `options/`). Custom views referencing old paths must be updated.
- **Composer constraints tightened:** `"livewire/livewire": "^4.0"` (was `"^3.1 || ^4"`), `"tallstackui/tallstackui": "^3.0"` (was `"^2.0"`).
- **HasFrontendAttributes:** `typeScriptAttributes()` no longer called by the DataTable. The `FormatterRegistry` discovers formatters through cast metadata, not through the `HasFrontendAttributes` trait. Models using this trait for `detailRoute()` and icon support are unaffected.
- **`getConfig()` removed:** Was used to bootstrap Alpine state with formatters, operators, and column metadata. No longer needed.
- **Row click events changed:** `data-table-row-clicked` dispatched via Livewire `$this->dispatch()` instead of Alpine event.

### What stays

- DataTable class definition: `$model`, `$enabledCols`, `$aggregatable`, `$isSelectable`, etc.
- Override hooks: `getBuilder()`, `getLayout()`, `getAppends()`, `getCellAttributes()`, `getRowAttributes()`, `$hasNoRedirect`
- `InteractsWithDataTables` interface on models
- `HasDatatableUserSettings` trait on User model
- `DatatableUserSetting` model + migration
- Search route / SearchController
- Config file

---

## Grouped View Architecture

In v1, grouped rows are built entirely in JS (~200 lines of HTML string generation). In v2, the grouped view is a Blade partial.

```
views/components/
├── grouped-table.blade.php      — Grouped layout with expand/collapse
├── group-header.blade.php       — Group header row (label, count, group aggregate)
└── group-rows.blade.php         — Rows within a group
```

- Expand/collapse state managed with Alpine `x-show` (no Livewire roundtrip)
- Per-group pagination dispatches Livewire action to load next page within group
- Per-group aggregates computed server-side alongside group data
- `loadGroupedData()` returns structure: `[['key' => ..., 'label' => ..., 'rows' => [...], 'aggregates' => [...], 'pagination' => [...]]]`

---

## Test Strategy

### Unit Tests (isolated, no Livewire)

- `FilterParser` — text input → filter array for various operators and edge cases
- `FilterApplier` — filter array → correct WHERE clauses (mock Builder)
- `RowTransformer` — model → raw+display array with various cast types
- `FormatterRegistry` — registration, lookup, auto-detection fallback
- Each Formatter — format() produces expected output for edge cases (null, empty, locale variants)

### Feature Tests (Livewire test helper)

- `DataTable` — mount, loadData, sorting, pagination, search, selection, aggregation
- `DataTableFilters` — filter building, text parsing, saved filters, dispatch to parent
- `DataTableOptions` — column toggle, aggregation config, grouping config, dispatch to parent
- Broadcasting — refreshRow updates single row, refreshData triggers full reload
- Grouped view — group loading, per-group pagination, expand/collapse

### Browser Tests (Pest v4 / Dusk)

- End-to-end table interactions: sort click, filter type, pagination navigate
- Drag & drop column reorder
- Sidebar open/close
- Keyboard shortcuts

Existing v1 tests are migrated where applicable. New classes (FilterParser, RowTransformer, formatters) get unit tests from the start.

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
