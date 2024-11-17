<?php

namespace TeamNiftyGmbH\DataTable\Tests\Browser;

use Faker\Factory;
use Laravel\Dusk\Browser;
use Livewire\Livewire;
use TeamNiftyGmbH\DataTable\Tests\BrowserTestCase;
use TeamNiftyGmbH\DataTable\Tests\Models\Data;

class DataTableTest extends BrowserTestCase
{
    public function browser(): Browser
    {
        return Livewire::visit(new class() extends \TeamNiftyGmbH\DataTable\DataTable
        {
            protected string $model = \TeamNiftyGmbH\DataTable\Tests\Models\Data::class;

            public array $enabledCols = [
                'string',
                'integer',
            ];
        });
    }

    public function test_loads_without_data()
    {
        $this->browser()
            ->waitForLivewire()
            ->waitForTextIn('thead', 'STRING')
            ->waitForTextIn('thead', 'INTEGER')
            ->waitForTextIn('tbody', 'No data found')
            ->assertDontSeeIn('thead', 'FLOAT');
    }

    public function test_loads_data()
    {
        $factory = Factory::create();

        while (Data::count() < 20) {
            $a = new Data();
            $a->string = $factory->word();
            $a->integer = $factory->randomNumber();
            $a->float = $factory->randomFloat();
            $a->save();
        }

        /** @var Browser $browse */
        $browse = $this->browser();

        $browse->waitUsing(10, 1, fn () => $browse->script('
                let alpineComponent = Alpine.$data(document.querySelector("[tall-datatable]"));

                return alpineComponent.initialized === 1;'
        ))
            ->waitForLivewire()
            ->waitForTextIn('thead', 'STRING')
            ->waitForTextIn('thead', 'INTEGER')
            ->assertDontSeeIn('thead', 'FLOAT');

        $this->assertCount(17, $browse->elements('tbody tr'));
    }
}
