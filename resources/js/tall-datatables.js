import data_table from './components/data-table';
import formatters from './components/formatters';

window.formatters = formatters();

if (window.Alpine?.version) {
    window.Alpine.data('data_table', data_table);
    document.querySelectorAll('[x-data*="data_table"]').forEach(el => {
        window.Alpine.initTree(el);
    });
} else {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('data_table', data_table);
    });
}
