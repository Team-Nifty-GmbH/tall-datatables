<?php

namespace TeamNiftyGmbH\DataTable\Support;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use TeamNiftyGmbH\DataTable\DataTable;

/**
 * Turns the settings of a saved filter back into a query, outside of a screen.
 *
 * The evaluation lives in the data table itself (`BuildsQueries::buildSearch()`,
 * groups combined with OR, the conditions inside a group with AND, plus the
 * relative date handling), so the table evaluates its own filter, called through
 * a closure bound to it. A public method on the table would be callable from the
 * browser. The class comes out of the container, which is what projects bind
 * their own table to, and a subclass of ours would lose exactly those overrides.
 */
class SavedFilterQuery
{
    /**
     * @param  class-string<DataTable>  $dataTable
     * @param  array  $settings  the stored settings of a saved filter
     *
     * @throws InvalidArgumentException when the filter holds no condition
     */
    public function resolve(string $dataTable, array $settings): Builder
    {
        $table = app($dataTable);

        return Closure::bind(
            function () use ($settings): Builder {
                // Conditions on hidden columns are dropped by the table, so they are
                // dropped before counting what is left, not after.
                $filters = array_values(array_filter(
                    $this->withoutHiddenFilters(data_get($settings, 'userFilters', [])),
                    fn ($group) => is_array($group) && $group !== []
                ));

                // A filter without conditions matches every row there is. Read as a
                // source that is not an empty result, it is a missing one, and the
                // difference decides whether a caller gets six digits of rows or none.
                if ($filters === []) {
                    throw new InvalidArgumentException(__('The saved filter holds no condition.'));
                }

                $this->mount();

                // Isolated from whatever the interface cached under the table's
                // own key, so a session filter of a user cannot narrow a query
                // that runs for somebody else or in the background.
                $this->cacheKey = 'saved-filter-query.' . static::class;
                $this->userFilters = $filters;
                $this->textFilters = [];
                $this->search = '';

                return $this->buildSearch(unpaginated: true);
            },
            $table,
            $table::class
        )();
    }
}
