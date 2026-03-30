# Tall DataTables 2.x — Design Guide

## Principles
- Consistent with flux-core widget styling patterns
- TallStackUI components everywhere (no raw HTML checkboxes, buttons, inputs)
- Mobile-aware but desktop-first
- Minimal, clean, no visual noise

## Typography
| Element | Class | Example |
|---------|-------|---------|
| Tab titles | TallStackUI default | Tab component handles it |
| Section labels | `text-xs font-medium tracking-wide text-gray-500 dark:text-gray-400` | "Spalten", "Verknüpfungen" |
| Body text | `text-sm text-gray-700 dark:text-gray-300` | Checkbox labels, relation names |
| Metadata/hints | `text-xs text-gray-400 dark:text-gray-500` | Help text, like-filter explanation |
| Column headers (table) | `text-sm font-semibold text-gray-500 dark:text-gray-50` | Table thead |
| Cell text | `text-sm` | Table body cells |

## Spacing
| Context | Gap/Padding |
|---------|-------------|
| Form fields (stacked) | `gap-2` |
| Between sections | `pt-3` or `pt-4` with `border-t` separator |
| Checkbox lists | `py-1` per item (compact) |
| Badge groups | `gap-2` |
| Grid columns (sidebar) | `gap-1.5` |
| Table cells | `px-3 py-4` |
| Filter row cells | `px-2 py-1` |

## Colors
| Element | Light | Dark |
|---------|-------|------|
| Backgrounds | `bg-white` | `dark:bg-secondary-800` |
| Borders | `border-gray-200` | `dark:border-secondary-700` |
| Hover rows | `hover:bg-gray-100` | `dark:hover:bg-secondary-900` |
| Group headers | `bg-gray-100` | `dark:bg-secondary-700` |
| Filter row | `bg-gray-50` | `dark:bg-secondary-600` |
| Primary actions | TallStackUI `color="primary"` | |
| Destructive | TallStackUI `color="red"` | |
| Secondary | TallStackUI `color="secondary" light` | |

## Badge Colors (Filter Badges)
| Badge type | Color |
|------------|-------|
| Search | default (no color) |
| Text filter | default (no color) |
| Sidebar filter | `color="indigo"` |
| AND connector | `color="red"` |
| OR connector | `color="emerald"` |
| Sort order | `color="amber"` |
| Group by | `color="cyan"` |

## Components (always TallStackUI)
- `<x-input>` — text inputs, search fields
- `<x-select.native>` — static dropdowns
- `<x-select.styled>` — async/dynamic selects
- `<x-checkbox>` — all checkboxes (sm size in lists)
- `<x-radio>` — radio buttons (with shared `name` attribute)
- `<x-button>` — all buttons, use `text` prop for static labels
- `<x-button.circle>` — icon-only buttons (delete, close)
- `<x-badge>` — filter badges, status indicators
- `<x-icon>` — all icons (Heroicons)
- `<x-tab>` / `<x-tab.items>` — sidebar tabs
- `<x-card>` — card containers
- `<x-modal>` — dialogs
- `<x-number>` — numeric inputs
- `<x-loading>` — NOT used for datatable (scoped wire:loading instead)

## Sidebar Layout
1. **Enabled columns** (top) — sortable checkboxes, `border-b` separator below
2. **Reset button** — left-aligned, `color="secondary" light`
3. **Breadcrumb** — `x-button xs flat color="primary"` for navigation
4. **Section labels** — `text-xs uppercase tracking-wider` above each column
5. **Two-column grid** — Columns left, Relations right, each with search

## Table Layout
1. **Header row** — Alpine `x-for` over `$wire.enabledCols`, TallStackUI `x-icon` for sort
2. **Filter row** — Alpine `x-for`, `x-input` for text, `x-select.native` for value lists
3. **Body** — Livewire Island (`@island(name: 'body')`)
4. **Footer** — Livewire Island (`@island(name: 'footer')`)
5. **Badges** — Livewire Island (`@island(name: 'badges')`)
6. **Loading** — `wire:loading` with scoped SVG spinner (no `x-loading`)

## Don'ts
- No raw `<input>`, `<select>`, `<button>` elements
- No `x-loading` from TallStackUI (it's page-global)
- No `$wire.entangle()` (use `$wire` proxy directly)
- No `@` event syntax (use `x-on:`)
- No `:` attribute binding syntax (use `x-bind:`)
- No `x-bind:disable` on `x-select.native` (wrap in div with `pointer-events-none`)
