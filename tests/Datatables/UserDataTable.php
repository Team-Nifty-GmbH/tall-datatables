<?php

namespace Tests\Datatables;

use Illuminate\View\ComponentAttributeBag;
use TeamNiftyGmbH\DataTable\DataTable;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableButton;
use TeamNiftyGmbH\DataTable\Htmlables\DataTableRowAttributes;
use Tests\Models\User;

class UserDataTable extends DataTable
{
    public array $enabledCols = [
        'id',
        'name',
        'email',
        'email_verified_at',
        'created_at',
    ];

    public array $formatters = [
        'email_verified_at' => 'datetime',
        'created_at' => 'date',
    ];

    protected string $model = User::class;

    public function mount(): void
    {
        parent::mount();

        $this->isSearchable = true;
        $this->isSelectable = true;
    }

    protected function getTableActions(): array
    {
        return [
            DataTableButton::make()
                ->text('Create User')
                ->icon('plus')
                ->color('indigo')
                ->attributes([
                    'x-on:click' => <<<'JS'
                            $dispatch('create-user')
                    JS
                ]),
        ];
    }

    protected function getRowActions(): array
    {
        return [
            DataTableButton::make()
                ->text('Edit')
                ->icon('pencil')
                ->color('indigo')
                ->attributes([
                    'x-on:click' => '$wire.edit(record.id); $event.stopPropagation()',
                ]),
            DataTableButton::make()
                ->text('Delete')
                ->icon('trash')
                ->color('red')
                ->attributes([
                    'x-on:click' => '$wire.delete(record.id); $event.stopPropagation()',
                ]),
        ];
    }

    public function delete($id): void
    {
        // Method stub for testing
    }

    public function edit($id): void
    {
        // Method stub for testing
    }

    protected function getRowAttributes(): ComponentAttributeBag
    {
        return DataTableRowAttributes::make()
            ->class('cursor-pointer')
            ->on('click', 'alert("Row clicked")')
            ->bind('class', 'record.email_verified_at ? "bg-green-100 dark:bg-green-900" : "bg-red-100 dark:bg-red-900"');
    }
}
