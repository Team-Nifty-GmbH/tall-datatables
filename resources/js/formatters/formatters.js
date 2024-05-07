export default {
    label: null,
    setLabel(label) {
        this.label = label;

        return this;
    },
    format({value, type, options, context}) {
        if (value === null) {
            return value;
        }

        if (Array.isArray(value)) {
            return this.array(value);
        }

        if (typeof type === 'object') {
            options = type[1];
            type = type[0];
        }

        if (this[type]) {
            return this[type](value, options, context);
        }

        const guessedType = this.guessType(value);
        if (this[guessedType]) {
            return this[guessedType](value, options, context);
        }

        return value;
    },
    money(value, currency = null, context) {
        if (value === null) {
            return value;
        }

        const documentCurrencyCode = document.querySelector('meta[name="currency-code"]')?.getAttribute('content');

        let currencyCode;

        if (currency === null && documentCurrencyCode) {
            currencyCode = documentCurrencyCode;
        } else if (typeof currency === 'string') {
            currencyCode = currency;
        } else if (typeof currency === 'object' && currency?.hasOwnProperty('property')) {
            currencyCode = context[currency.property];
        } else if (
            typeof currency === 'object'
            && currency?.hasOwnProperty('currency')
            && currency?.currency?.hasOwnProperty('iso')
        ) {
            currencyCode = currency.currency.iso;
        } else if (typeof currency === 'object' && currency?.hasOwnProperty('iso')) {
            currencyCode = currency.iso;
        } else {
            currencyCode = documentCurrencyCode;
        }

        if (! (typeof currencyCode === 'string')) {
            return this.float(value);
        }

        try {
            return new Intl.NumberFormat(document.documentElement.lang, {
                style: 'currency',
                currency: currencyCode,
                minimumFractionDigits: 2,
            }).format(value);
        } catch (e) {
            return this.float(value) + ' ' + currencyCode;
        }
    },
    coloredMoney(value, currency = null, context) {
        const returnValue = this.money(value, currency, context);
        if (value < 0) {
            return `<span class="text-negative-500 dark:text-negative-700 font-semibold">${returnValue}</span>`;
        } else {
            return `<span class="text-positive-500 dark:text-positive-700 font-semibold">${returnValue}</span>`;
        }
    },
    percentage(value) {
        const percentageFormatter = new Intl.NumberFormat(document.documentElement.lang, {
            style: 'percent',
            minimumFractionDigits: 2,
        });

        return percentageFormatter.format(value);
    },
    bool(value) {
        if (value === 'false' || value === false || value === 0 || value === '0' || value === null) {
            return `<span class="bg-negative-500 dark:bg-negative-700 group inline-flex h-6 w-6 items-center justify-center rounded-full text-white outline-none">
                        <svg class="h-3 w-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </span>`;
        } else {
            return `<span class="bg-positive-500 dark:bg-positive-700 group inline-flex h-6 w-6 items-center justify-center rounded-full text-white outline-none">
                    <svg class="h-3 w-3 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    </span>`;
        }
    },
    progressPercentage(value) {
        const formatter = new Intl.NumberFormat('en-US', {style: 'percent'});
        return `<div class="relative pt-1">
                    <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                        <div style="width:${formatter.format(value)}" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-primary-500 dark:bg-primary-700"></div>
                    </div>
                    <span>${this.percentage(value)}</span>
                </div>`;
    },
    array(value) {
        if (! Array.isArray(value)) {
            return value;
        }

        return value.map(item => {
            return this.badge(item, 'indigo');
        })
            .join(' ');
    },
    object(value) {
        if (Object.keys(value).every(key => !isNaN(parseInt(key)) && isFinite(key))) {
            value = Array.from(value);

            return this.array(value);
        }

        return Object.keys(value).map(key => {
            const type = this.guessType(value[key]);
            const val = this.format({value: value[key], type: type});

            return this.badge(`${key}: ${val}`, 'indigo');
        }).join(' ');
    },
    boolean(value) {
        return this.bool(value);
    },
    date(value) {
        return new Date(value).toLocaleDateString(document.documentElement.lang, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
        });
    },
    datetime(value) {
        return new Date(value).toLocaleString(document.documentElement.lang, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
        });
    },
    badge(value, colors) {
        if (! Boolean(value)) {
            return null;
        }

        const color = colors[value] || colors;
        if (this.label) {
            value = this.label;
        }

        return '<span class="outline-none inline-flex justify-center items-center group rounded gap-x-1 text-xs font-semibold px-2.5 py-0.5 text-' + color + '-600 bg-' + color + '-100 dark:text-' + color + '-400 dark:bg-slate-700">\n' +
            value + '\n' +
            '</span>';
    },
    relativeTime(value) {
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
    time(value) {
        // check if value is already in time format
        if (typeof value === 'string' && value.match(/^(?:2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/)) {
            return value;
        }

        let seconds = Math.floor(value / 1000);
        let minutes = Math.floor(seconds / 60);
        seconds = seconds % 60;
        let hours = Math.floor(minutes / 60);
        minutes = minutes % 60;

        hours = hours.toString().padStart(2, '0');
        minutes = minutes.toString().padStart(2, '0');
        seconds = seconds.toString().padStart(2, '0');

        return `${hours}:${minutes}:${seconds}`;
    },
    float(value) {
        if (isNaN(parseFloat(value))) {
            return value;
        }
        let val = parseFloat(value);

        try {
            return val.toLocaleString(document.documentElement.lang);
        } catch (e) {
            return val;
        }
    },
    coloredFloat(value) {
        return this.coloredMoney(value, '');
    },
    int(value) {
        return parseInt(value);
    },
    string(value) {
        if (value === null) {
            return value;
        }

        if (this.label) {
            value = this.label;
        }

        return value.toString();
    },
    state(value, colors) {
        return this.badge(value, colors);
    },
    image(value) {
        if (! value) {
            return value;
        }

        return '<div class="dark:border-secondary-500 inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full border border-gray-200 dark:bg-gray-200">\n' +
            '    \n' +
            '            <img class="h-8 w-8 shrink-0 rounded-full object-contain object-center text-xl" src="' + value + '">\n' +
            '    \n' +
            '    </div>';
    },
    email(value) {
        return '<a href="mailto:' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    url(value) {
        return '<a href="' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    tel(value) {
        return '<a href="tel:' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    link(value) {
        return '<a href="' + value + '" class="text-primary-500 dark:text-primary-400">' + value + '</a>';
    },
    inputType(value) {
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
    },
    guessType(value) {
        if (value === null) {
            return 'null';
        }

        if (Array.isArray(value)) {
            return 'array';
        }

        if (typeof value === 'object') {
            return 'object';
        }

        if (typeof value === 'boolean') {
            return 'boolean';
        }

        if (typeof value === 'string') {
            if (value.includes('://')) {
                return 'url';
            }

            if (value.includes('@')) {
                return 'email';
            }

            if (value.match(/^\d{4}-\d{2}-\d{2}(T|\s)\d{2}:\d{2}(:\d{2})?$/)) {
                return 'datetime';
            }

            if (value.match(/^\d{4}-\d{2}-\d{2}$/)) {
                return 'date';
            }

            if (value.match(/^\d{2}:\d{2}:\d{2}$/)) {
                return 'time';
            }

            if (value.match(/^\d+$/)) {
                return 'int';
            }

            if (value.match(/^\d+\.\d+$/)) {
                return 'float';
            }

            return 'string';
        }

        if (typeof value === 'number') {
            if (value % 1 === 0) {
                return 'int';
            }

            return 'float';
        }

        return 'string';
    }
}
