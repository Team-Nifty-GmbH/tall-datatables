# Roadmap

## Themes

### User Personalization

- **Shared / Published Saved Filters** — Allow users to share their saved filter presets with the team or make them publicly available for all users of a DataTable.
- **User-controlled Default Columns** — Let users define which columns are visible by default when they open a DataTable, persisted per user.
- **Row Drag & Drop (opt-in)** — Optional drag & drop support for manual row reordering (e.g. updating a `sort_order` field). Column drag & drop already exists.

### UX

- **Positive Empty State (opt-in)** — Show a happy emoji instead of the sad one when a table is empty. Some tables are expected to be empty (e.g. error logs, open issues) and an empty state should feel like a good thing.
- **Multi-Sort** — Sort by multiple columns simultaneously via Shift+Click on additional column headers.
- **Saved Views (opt-in)** — Save the complete table state (filters, columns, sorting, layout) as a named view. Opt-in per DataTable to allow users to create and switch between saved views.

### Filter System

- **Extended Filter Operators** — Add `in`, `not in`, `starts with`, `contains` operators and support for custom operator callbacks.
- **Relative Date Filters** — Predefined date ranges like "Last 30 days", "This month", "This quarter", "Last year".
- **Configurable Date Format** — Make the date parsing format configurable instead of the hardcoded `dd.mm.yyyy`.

### Layouts

- **Kanban View** — A Kanban board layout as a third option alongside table and grid. Cards grouped by a configurable status/category column with drag & drop between lanes.

### Export

- **CSV Export** — Lightweight export without the Excel dependency.
- **JSON Export** — Structured export for BI tools and API integrations.
- **Streaming Export** — Chunked/streaming export for large datasets (100k+ rows) to avoid memory issues.

### Accessibility

- **ARIA Attributes** — Semantic HTML with `aria-sort`, `role` attributes and proper table structure.
- **Keyboard Navigation** — Arrow key and tab navigation through cells and rows.

### Aggregation

- **Relation Count Columns** — Add `withCount` based columns for relations (e.g. "Orders Count" for a contact). Displayed as a regular column with filter input in the header. Filtering on count columns uses HAVING clauses.

### Extensibility

- **Action Builder DSL** — A fluent API for defining row, table and bulk actions instead of raw arrays.
- **Sidebar Tabs Documentation** — Document the existing `getSidebarTabs()` extensibility hook with examples.
- **Formatter Plugin Documentation** — Document the `FormatterRegistry` and how to register custom formatters.

### Documentation

- **Extended README** — Cover advanced filtering, custom formatters, actions, performance tuning and customization patterns.

---

## Releases

### v2.1 — User Personalization ✓

- [x] Shared / published saved filters
- [x] User-controlled default columns
- [x] Row drag & drop (opt-in)
- [x] Positive empty state (opt-in)
- [x] Multi-sort
- [x] Relation count columns

### v2.2 — Filters, Export & Views

- [ ] Saved views (opt-in)
- [ ] Extended filter operators (`in`, `not in`, `starts with`, `contains`, custom)
- [ ] Relative date filters
- [x] CSV export
- [x] JSON export

### v2.3 — Kanban, Accessibility & DX

- [ ] Kanban view layout
- [ ] ARIA attributes & semantic HTML
- [ ] Keyboard navigation
- [ ] Action builder DSL
- [ ] Sidebar tabs & formatter plugin documentation
- [ ] Extended README

### Future

- [ ] Configurable date format (breaking: changes default parsing behavior)
- [ ] Streaming export for large datasets
