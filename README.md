# A package to create datatables using alpinejs, tailwind, livewire and laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/team-nifty-gmbh/tall-datatables.svg?style=flat-square)](https://packagist.org/packages/team-nifty-gmbh/tall-datatables)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/team-nifty-gmbh/tall-datatables/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/team-nifty-gmbh/tall-datatables/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/team-nifty-gmbh/tall-datatables/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/team-nifty-gmbh/tall-datatables/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/team-nifty-gmbh/tall-datatables.svg?style=flat-square)](https://packagist.org/packages/team-nifty-gmbh/tall-datatables)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Requirements

- PHP >= 8.1
- Laravel >= 9.46
- AlpineJS >= 3.0
- TailwindCSS >= 3.0
- Livewire >= 2.11
- Vite >= 4.0
- MeiliSearch >= 0.30

## Installation

1. You can install the package via composer:

```bash
composer require team-nifty-gmbh/tall-datatables
```
2. Add the scripts tag to your layout BEFORE alpinejs
```html
...
<livewire:scripts/>

<wireui:scripts />
<datatable:scripts />

@vite(['resources/js/alpine.js'])
...
```

Keep in mind to follow the wireui installation instructions starting at step 2:
https://livewire-wireui.com/docs/get-started

3. Add the folowing to your tailwind.config.js

```js
module.exports = {
    presets: [
        ...
        require('./vendor/team-nifty-gmbh/tall-datatables/tailwind.config.js')
    ],
    content: [
        ...
        './vendor/team-nifty-gmbh/tall-datatables/resources/views/**/*.blade.php',
        './vendor/team-nifty-gmbh/tall-datatables/resources/js/**/*.js',
    ],
    ...
}
```

4. Run vite build to compile the javascript and css files

```bash
vite build
```

5. Publishing the views is optional. If you want to use the default views you can skip this step.

Optionally, you can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="tall-datatables-migrations"
php artisan migrate
```

Optionally, you can publish the config file with:

```bash
php artisan vendor:publish --tag="tall-datatables-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="tall-datatables-views"
```

# Usage

This command creates a new DataTable class.
```shell
php artisan make:data-table UserDataTable "App\Models\User"
```

Inside this class you should define at least the columns you want to display

```php
public array $enabledCols = [
    'id',
    'name',
    'email',
    'email_verified_at',
    'created_at',
    'updated_at',
];
```

### Adding Buttons to a row

> **_NOTE:_** Keep in mind that tall-datatables relies on alpinejs to render the data.
> 
> Each row is rendered using the `x-for` directive. This means that every record is available as a variable called `record`.
> 
> Remember that the record variable contains only the columns that are returned by the `getReturnKeys` php method.
> The Model key is always available.

You can add buttons to a row by overriding the getRowActions method.
Check the WireUi documentation for the available options.


```php
use TeamNiftyGmbH\DataTable\Htmlables\DataTableRowButton;

...

public function getRowActions(): array
{
    return [
        \TeamNiftyGmbH\DataTable\Htmlables\DataTableRowButton::make()
            ->label('Edit')
            ->icon('eye')
            ->color('primary')
            ->attributes([
                'x-on:click' => '$wire.edit(record.id)',
                'x-bind:class' => 'record.is_locked ? \'hidden\' : \'\''
            ]),
        \TeamNiftyGmbH\DataTable\Htmlables\DataTableRowButton::make()
            ->label('Delete')
            ->icon('trash')
            ->color('negative'),
    ];
}
```

### Adding Attributes to a row
You can add attributes to a row by overriding the getRowAttributes method.

```php
use TeamNiftyGmbH\DataTable\Htmlables\DataTableRowAttributes;

...

public function getRowAttributes(): array
{
    return DataTableRowAttributes::make()
        ->bind('class', 'record.is_active ? \'bg-green-100\' : \'bg-red-100\'')
        ->on('click', 'alert($event.detail.record.id)')
        ->class('cursor-pointer')
}
```

### Eager Loading
If you need to eager load additional data you can override the getBuilder method

```php
public function getBuilder(Builder $builder): Builder
{
    return $builder->with('roles');
}
```

### Minimized Network traffic
The datatable component will only return the columns you defined in the enabledCols property.
In case you need a specific column to be always returned you can override the getReturnKeys method.

This is especially needed when you want to format money values in the frontend.

```php
public function getReturnKeys(): array
{
    return array_merge(parent::getReturnKeys(), ['currency.iso']);
}
```

### Using your DataTable inside another component
To use this new Data table you can add a livewire tag in your blade file:
    
```html
<livewire:data-tables.user-data-table />
```

you can pass contextual attributes when you call the component like this:

```html
<livewire:data-tables.user-data-table 
    :searchable="false" 
    :filters="[['is_active' => true]]" 
/>
```
This keeps your component reusable and you can use it in different contexts.

### Row clicked
> **_NOTE:_** The data-table-row-clicked event is always dispatched, however if your record has an href attribute the click will open the link.
> 
> If you just need the click event you should set the href attribute to `javascript:void(0);` or completely remove from your record.


Every row click dispatches a `data-table-row-clicked` event with the model as payload.
You can listen to this event in your AlpineJS.

```html
<div x-data="{ ... }" x-on:data-table-row-clicked="console.log($event.detail)">
    <livewire:data-tables.user-data-table />
</div>
```
If you want to use your clicked row with livewire my recommendation is to use the `$wire` property from alpinejs.

```html
<div x-data="{ ... }" x-on:data-table-row-clicked="$wire.set('user', $event.detail)">
    <livewire:data-tables.user-data-table />
</div>
```

# Prepare your model

## HasFrontendFormatter Concern

If you want to format the data for the frontend you should use the HasFrontendAttributes trait
in your model. This trait will add a method to your model called `getFrontendAttributes()`

Also, you should define a detailRouteName property in your model which points to a view showing the details of the model.

```php
use TeamNifty\TallDatatables\Traits\HasFrontendAttributes;

class User extends Authenticatable
{
    use HasFrontendAttributes;
    
    protected string $detailRouteName = 'users.id';
    ...
}
```

If your detail route needs additional parameters you can override the `getDetailRouteParameters()` method in your model class.

```php
public function getDetailRouteParameters(): array
{
    return [
        'id' => $this->id,
        'foo' => 'bar',
    ];
}
```

The trait adds an attribute accessor to your model which contains the detail route for a single model instance.

```php
$user = User::first();
$user->href; // returns the detail route for the user
```

You can set an iconName property in your model which will be used to display an icon in the table.
You can set any icon from the [heroicons](https://heroicons.com/) library.

```php
protected string $iconName = 'user';
```

## Casts

The Package uses casts to format the data for the frontend. You can define your own casts in the `casts` property of your model.
Aside from the primitive cast you can add your own casts. These cast classes should implement the `TeamNifty\TallDatatables\Contracts\HasFrontendFormatter` interface
which brings the `getFrontendFormatter()` method.

```php
use TeamNifty\TallDatatables\Casts\Date;
```

## Searchable

If you want to search in your datatable you should use the Searchable trait from laravel scout.
The package will automatically detect if your model is searchable and will add a search input to the datatable.

If you dont want to use the search input you can set the isSearchable property to false in your DataTable.
    
```php
public bool $isSearchable = false;
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Patrick Weh](https://github.com/patrickweh)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
