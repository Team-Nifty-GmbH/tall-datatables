import data_table from './components/data-table.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('data_table', data_table);
});
