# Tall DataTables

[![Latest Version on Packagist](https://img.shields.io/packagist/v/team-nifty-gmbh/tall-datatables.svg?style=flat-square)](https://packagist.org/packages/team-nifty-gmbh/tall-datatables)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/Team-Nifty-GmbH/tall-datatables/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Team-Nifty-GmbH/tall-datatables/actions?query=workflow%3ATests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/team-nifty-gmbh/tall-datatables.svg?style=flat-square)](https://packagist.org/packages/team-nifty-gmbh/tall-datatables)

Performant, feature-rich datatables for the TALL stack. Built on Livewire 4 Islands architecture with Alpine.js driven rendering for minimal payloads.

## Requirements

- PHP 8.4+
- Laravel 12+
- Livewire 4+
- TallStackUI 3+
- Alpine.js 3+
- Tailwind CSS 3+

## Installation

```bash
composer require team-nifty-gmbh/tall-datatables:^2.0
```

Add the scripts and styles to your layout:

```html
<datatable:styles />
<datatable:scripts />
```

Publish migrations and run them:

```bash
php artisan vendor:publish --tag="tall-datatables-migrations"
php artisan migrate
```

## Quick Start

```php
class UserDataTable extends DataTable
{
    protected string $model = User::class;

    public array $enabledCols = ['name', 'email', 'created_at'];
}
```

```html
<livewire:data-tables.user-data-table />
```

## Layouts

Three built-in layouts: **table** (default), **grid**, and **kanban**.

### View-Mode Switcher

Let users switch between layouts at runtime:

```php
protected function availableLayouts(): array
{
    return ['table', 'grid', 'kanban'];
}
```

When more than one layout is configured, a switcher appears in the toolbar. The active layout is persisted in saved filters.

### Grid Layout

Cards in a responsive CSS grid. Image columns get hero treatment.

```php
protected function availableLayouts(): array
{
    return ['table', 'grid'];
}
```

### Kanban Layout

Cards grouped into lanes by a column value. Drag & drop between lanes.

```php
protected function availableLayouts(): array
{
    return ['table', 'kanban'];
}

// Which column determines the lanes
protected function kanbanColumn(): string
{
    return 'state';
}

// Optional: explicit lane config (order, labels, colors)
protected function kanbanLanes(): ?array
{
    return [
        'open' => ['label' => 'Open', 'color' => 'emerald'],
        'in_progress' => ['label' => 'In Progress', 'color' => 'amber'],
        'done' => ['label' => 'Done', 'color' => 'indigo'],
    ];
}

// Handle drag & drop
public function kanbanMoveItem(int|string $id, string $targetLane): void
{
    MyModel::findOrFail($id)->update(['state' => $targetLane]);
}

// Optional: custom card blade view
protected function kanbanCardView(): ?string
{
    return 'components.my-kanban-card';
}
```

If `kanbanLanes()` returns `null`, lanes are auto-generated from the column's enum/state values.

## Filtering

### Sidebar Filters

Enable filtering with the sidebar:

```php
public bool $isFilterable = true;
```

Available operators: `=`, `!=`, `>`, `>=`, `<`, `<=`, `like`, `not like`, `starts with`, `ends with`, `contains`, `does not contain`, `in`, `not in`, `is null`, `is not null`, `between`.

The `in` and `not in` operators accept comma-separated values.

### Date Presets

Date columns automatically show a preset dropdown: Today, Yesterday, This Week/Month/Quarter/Year, Last 7/30 Days, Last Week/Month/Quarter/Year. A "Custom" option opens inline fields for building custom relative date calculations.

### Saved Filters

Users can save their current filter/column/sort configuration and reload it later. Supports shared filters across team members:

```php
protected function canShareFilters(): bool
{
    return true;
}
```

## Sorting

Click a column header to sort. Shift+Click for multi-sort on additional columns.

```php
// Restrict sortable columns (default: all)
public array $sortable = ['name', 'created_at'];
```

## Selection

```php
public bool $isSelectable = true;
```

Select individual rows or use "select all" (wildcard). The wildcard stays compact (`['*']`) regardless of record count. Use `getSelectedValues()` or `getSelectedModels()` in your actions to resolve the actual records.

## Relation Columns

Display columns from related models using dot notation:

```php
public array $enabledCols = [
    'name',
    'email',
    'department.name',
    'department.manager.email',
];
```

### Relation Count Columns

Show relation counts by adding `_count` suffix in the column configuration. Counts are filterable and sortable:

```php
public array $enabledCols = ['name', 'orders_count', 'comments_count'];
```

## Export

Export data in three formats: Excel (.xlsx), CSV, and JSON. Users select the format from a dropdown in the export tab.

```php
// Disable export (enabled by default)
public bool $isExportable = false;
```

CSV uses semicolon delimiter with UTF-8 BOM for Excel compatibility. JSON exports relations as nested objects.

## Row Actions

Define actions using `DataTableButton`:

```php
protected function getRowActions(): array
{
    return [
        DataTableButton::make('edit')
            ->label(__('Edit'))
            ->icon('pencil')
            ->wireClick('edit(record.id)'),
    ];
}
```

## Row Drag & Drop

Enable manual row reordering:

```php
protected function getRowDragDropConfig(): ?array
{
    return [
        'column' => 'sort_order',
    ];
}
```

## Custom Formatters

Register formatters for custom display logic:

```php
use TeamNiftyGmbH\DataTable\Formatters\FormatterRegistry;

app(FormatterRegistry::class)->register(MyCast::class, new MyFormatter());
```

Built-in formatters: `boolean`, `date`, `datetime`, `money`, `percentage`, `image`, `link`, `badge`, `float`, `string`, `array`.

### Custom Column Transformation

Use `augmentItemArray()` to add computed columns before formatters are applied:

```php
protected function augmentItemArray(array &$itemArray, Model $item): void
{
    $itemArray['full_name'] = $item->first_name . ' ' . $item->last_name;
}
```

## Sidebar Tabs

Extend the sidebar with custom tabs:

```php
public function getSidebarTabs(): array
{
    $tabs = parent::getSidebarTabs();

    $tabs[] = [
        'id' => 'my-tab',
        'label' => __('My Tab'),
        'view' => 'components.my-custom-tab',
    ];

    return $tabs;
}
```

## Positive Empty State

Show a friendly message when a table is expected to be empty:

```php
public bool $positiveEmptyState = true;
```

## Default Columns

Allow users to save their column layout as the default:

```php
protected function canSaveDefaultColumns(): bool
{
    return true;
}
```

## Testing

```bash
vendor/bin/pest
```

## Credits

- [Patrick Weh](https://github.com/patrickweh)
- [All Contributors](../../contributors)

## License

MIT. See [LICENSE](LICENSE.md).
