# A package to create datatables using alpinejs, tailwind, livewire and laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/team-nifty-gmbh/tall-datatables.svg?style=flat-square)](https://packagist.org/packages/team-nifty-gmbh/tall-datatables)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/team-nifty-gmbh/tall-datatables/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/team-nifty-gmbh/tall-datatables/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/team-nifty-gmbh/tall-datatables/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/team-nifty-gmbh/tall-datatables/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/team-nifty-gmbh/tall-datatables.svg?style=flat-square)](https://packagist.org/packages/team-nifty-gmbh/tall-datatables)

This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/tall-datatables.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/tall-datatables)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Installation

1. You can install the package via composer:

```bash
composer require team-nifty-gmbh/tall-datatables
```
2. add the scripts tag to your layout BEFORE alpinejs
```html
...
<livewire:scripts/>
<datatable:scripts />
@vite(['resources/js/alpine.js'])
...
```

alternative you can add the script to your vite config, but again keep in mind to load the
script BEFORE alpinejs as it needs to listen on the alpine:init event

```js
export default defineConfig({
    plugins: [
        laravel({
            input: [
                ...
                'vendor/team-nifty-gmbh/tall-datatables/resources/js/tall-datatables.js',
            ],
        }),
    ],
    ...
})
```

in your layout add the vite import:
```html
...
@vite([
    ...
    'vendor/team-nifty-gmbh/tall-datatables/resources/js/tall-datatables.js',
    ...
])
@vite(['resources/js/alpine.js'])
...
```
3. Add the folowing to your tailwind.config.js

```js
module.exports = {
    presets: [
        ...
        require('./vendor/team-nifty-gmbh/tall-datatables/tailwind.config.js')
    ]
}
```

4. run vite build to compile the javascript files

```bash
vite build
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="tall-datatables-migrations"
php artisan migrate
```

You can publish the config file with:

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

inside this class you should define at least the columns you want to display

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

if you need to eager load additional data you can override the getBuilder method

```php
public function getBuilder(Builder $builder): Builder
{
    return $builder->with('roles');
}
```

The datatable component will only return the columns you defined in the enabledCols property.
In case you need a specific column to be always returned you can override the getReturnKeys method.

This is especially needed when you want to format money values in the frontend.

```php
public function getReturnKeys(): array
{
    return array_merge(parent::getReturnKeys(), ['currency.iso']);
}
```

# Models

## HasFrontendFormatter Conern

When you want to format the data for the frontend you should use the HasFrontendAttributes trait
in your model. This trait will add a method to your model called `getFrontendAttributes()`

Also you should define a detailRouteName property in your model which points to a view showing the details of the model.

```php
use TeamNifty\TallDatatables\Traits\HasFrontendAttributes;

class User extends Authenticatable
{
    use HasFrontendAttributes;
    
    protected string $detailRouteName = 'users.id';
    ...
}
```

if your detail route needs additional parameters you can override the `getDetailRouteParameters()` method in your model class.

```php
public function getDetailRouteParameters(): array
{
    return [
        'id' => $this->id,
        'foo' => 'bar',
    ];
}
```

the trait adds an attribute accessor to your model which contains the detailroute for a single model instance.

```php
$user = User::first();
$user->href; // returns the detail route for the user
```

you can set an iconName property in your model which will be used to display an icon in the table.
You can set any icon from the [heroicons](https://heroicons.com/) library.

```php
protected string $iconName = 'user';
```

## Casts

The Package uses casts to format the data for the frontend. You can define your own casts in the `casts` property of your model.
Aside of the primitive cast you can add your own casts. These cast classes should implement the `TeamNifty\TallDatatables\Contracts\HasFrontendFormatter` interface
which brings the `getFrontendFormatter()` method.

```php
use TeamNifty\TallDatatables\Casts\Date;
```

## Searchable

When you want to search in your datatable you should use the Searchable trait from laravel scut

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
