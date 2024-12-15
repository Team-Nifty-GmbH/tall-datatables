import data_table from './components/data-table';
import formatters from './components/formatters';

window.formatters = formatters();

document.addEventListener('alpine:init', () => {
    window.Alpine.data('data_table', data_table)
});
