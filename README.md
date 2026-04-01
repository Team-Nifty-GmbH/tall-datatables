# Tall DataTables

[![Latest Version on Packagist](https://img.shields.io/packagist/v/team-nifty-gmbh/tall-datatables.svg?style=flat-square)](https://packagist.org/packages/team-nifty-gmbh/tall-datatables)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/Team-Nifty-GmbH/tall-datatables/tests.yml?branch=2.x&label=tests&style=flat-square)](https://github.com/Team-Nifty-GmbH/tall-datatables/actions?query=workflow%3ATests+branch%3A2.x)
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

```bash
php artisan make:data-table UserDataTable "App\Models\User"
```

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

Two built-in layouts: table (default) and grid.

```php
protected function getLayout(): string
{
    return 'tall-datatables::layouts.grid';
}
```

## Testing

```bash
cd packages/tall-datatables
vendor/bin/testbench package:test
```

## Credits

- [Patrick Weh](https://github.com/patrickweh)
- [All Contributors](../../contributors)

## License

MIT. See [LICENSE](LICENSE.md).
