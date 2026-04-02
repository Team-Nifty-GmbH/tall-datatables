import data_table from './components/data-table.js';
import datatable_options from './components/datatable-options.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('data_table', data_table);
    window.Alpine.data('datatableOptions', datatable_options);
});
