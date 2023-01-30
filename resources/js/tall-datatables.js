import {Sortable} from 'sortablejs';
import Alpine from 'alpinejs'
import focus from '@alpinejs/focus'

Alpine.plugin(focus)
Sortable.mount();

window.formatters = {
    format: function ({value, type, options, context}) {
        if (value === null) {
            return value;
        }

        if (typeof type === 'object') {
            options = type[1];
            type = type[0];
        }

        if (this[type]) {
            return this[type](value, options, context);
        }

        return value;
    },
    money: (value, currency = null, context) => {
        if (value === null) {
            return value;
        }

        const documentCurrencyCode = document.querySelector('meta[name="currency-code"]')?.getAttribute('content');

        let currencyCode;

        if (currency === null && documentCurrencyCode) {
            currencyCode = documentCurrencyCode;
        } else if (typeof currency === 'string') {
            currencyCode = currency;
        } else if (typeof currency === 'object' && currency.hasOwnProperty('property')) {
            currencyCode = context[currency.property];
        } else if (
            typeof currency === 'object'
            && currency.hasOwnProperty('currency')
            && currency.currency.hasOwnProperty('iso')
        ) {
            currencyCode = currency.currency.iso;
        } else if (typeof currency === 'object' && currency.hasOwnProperty('iso')) {
            currencyCode = currency.iso;
        } else {
            currencyCode = documentCurrencyCode;
        }

        if (! (typeof currencyCode === 'string')) {
            return formatters.float(value);
        }

        try {
            return new Intl.NumberFormat(document.documentElement.lang, {
                style: 'currency',
                currency: currencyCode,
                minimumFractionDigits: 2,
            }).format(value);
        } catch (e) {
            return formatters.float(value) + ' ' + currencyCode;
        }
    },
    percentage: (value) => {
        const percentageFormatter = new Intl.NumberFormat(document.documentElement.lang, {
            style: 'percent',
            minimumFractionDigits: 2,
        });

        return percentageFormatter.format(value);
    },
    bool: (value) => {
        if (value === 'false' || value === false || value === 0 || value === '0' || value === null) {
            return `<span class="outline-none inline-flex justify-center items-center group rounded-full w-6 h-6 text-white bg-negative-500 dark:bg-negative-700">
                        <svg class="w-3 h-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </span>`;
        } else {
            return `<span class="outline-none inline-flex justify-center items-center group rounded-full w-6 h-6 text-white bg-positive-500 dark:bg-positive-700">
                    <svg class="w-3 h-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </span>`;
        }
    },
    array: (value) => {
        if (typeof value === 'array'){
            return value.join(', ');
        }

        return value;
    },
    object: (value) => {
        return JSON.stringify(value);
    },
    boolean: (value) => {
        return formatters.bool(value);
    },
    date: (value) => {
        return new Date(value).toLocaleDateString(document.documentElement.lang);
    },
    datetime: (value) => {
        return new Date(value).toLocaleString(document.documentElement.lang);
    },
    relativeTime: (value) => {
        const current = new Date().getTime();
        const elapsed = current - value;
        const timeFormatter = new Intl.RelativeTimeFormat(document.documentElement.lang, {style: 'narrow'});
        const seconds = elapsed / 1000;
        const minutes = seconds / 60;
        const hours = minutes / 60;
        const days = hours / 24;
        const weeks = days / 7;
        const months = days / 30;
        const years = days / 365;

        switch (true) {
            case seconds < 60:
                return timeFormatter.format(Math.round(seconds) * -1, 'second');
            case minutes < 60:
                return timeFormatter.format(Math.round(minutes) * -1, 'minute');
            case hours < 24:
                return timeFormatter.format(Math.round(hours) * -1, 'hour');
            case days < 7:
                return timeFormatter.format(Math.round(days) * -1, 'day');
            case weeks < 4:
                return timeFormatter.format(Math.round(weeks) * -1, 'week');
            case months < 12:
                return timeFormatter.format(Math.round(months) * -1, 'month');
            default:
                return timeFormatter.format(Math.round(years) * -1, 'year');
        }
    },
    time: (value) => {
        return new Date(value).toLocaleTimeString(document.documentElement.lang);
    },
    float: (value) => {
        if (isNaN(parseFloat(value))) {
            return value;
        }

        return parseFloat(value).toLocaleString(document.documentElement.lang);
    },
    int: (value) => {
        return parseInt(value);
    },
    string: (value) => {
        if (value === null) {
            return value;
        }

        return value.toString();
    },
    state: (value, colors) => {
        const color = colors[value];

        return '<span class="outline-none inline-flex justify-center items-center group rounded gap-x-1 text-xs font-semibold px-2.5 py-0.5 text-' + color + '-600 bg-' + color + '-100 dark:text-' + color + '-400 dark:bg-slate-700">\n' +
            value + '\n' +
            '</span>';
    },
    image: (value) => {
        if (! value) {
            return value;
        }

        return '<div class="shrink-0 inline-flex items-center justify-center overflow-hidden rounded-full border border-gray-200 dark:border-secondary-500 dark:bg-gray-200">\n' +
            '    \n' +
            '            <img class="shrink-0 object-contain object-center rounded-full w-8 h-8 text-xl" src="' + value + '">\n' +
            '    \n' +
            '    </div>';
    },
    email: (value) => {
        return '<a href="mailto:' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    url: (value) => {
        return '<a href="' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    tel: (value) => {
        return '<a href="tel:' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    link: (value) => {
        return '<a href="' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    inputType: (value) => {
        switch (value) {
            case 'datetime':
                return 'datetime-local';
            case 'date':
                return 'date';
            case 'time':
                return 'time';
            case 'number':
            case 'int':
            case 'float':
            case 'money':
                return 'number';
            case 'email':
                return 'email';
            case 'password':
                return 'password';
            case 'tel':
                return 'tel';
            case 'url':
                return 'url';
            default:
                return 'text';
        }
    }
}

window.Alpine = Alpine;
Alpine.start();
