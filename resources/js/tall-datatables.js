import data_table from './components/data-table.js';
import datatable_options from './components/datatable-options.js';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('data_table', data_table);
    window.Alpine.data('datatableOptions', datatable_options);
});

// ponytail: Livewire finds the island to morph by its token without stopping at nested
// components, and a nested data table renders the same view, so the same token. Its island
// markers are hidden while the outer component morphs. Drop once livewire/livewire#10737 lands.
document.addEventListener('livewire:init', () => {
    window.Livewire.interceptMessage(({ message, onSuccess, onFinish }) => {
        let hidden = [];

        const restore = () => {
            hidden.forEach((node) => (node.textContent = node.textContent.slice(1)));
            hidden = [];
        };

        onSuccess(({ payload, onMorph }) => {
            if (! payload.effects.islandFragments?.length) return;

            const root = message.component.el;
            const walker = document.createTreeWalker(root, NodeFilter.SHOW_COMMENT);

            while (walker.nextNode()) {
                const node = walker.currentNode;

                if (node.textContent.startsWith('[if FRAGMENT') && node.parentElement.closest('[wire\\:id]') !== root) {
                    node.textContent = '-' + node.textContent;
                    hidden.push(node);
                }
            }

            onMorph(restore);
        });

        onFinish(restore);
    });
});
