# Changelog

All notable changes to `tall-datatables` will be documented in this file.

## v0.4.1 - 2023-02-23

hotfix exception caused by wrong order in mount

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.4.0...v0.4.1

## v0.4.0 - 2023-02-23

### What's Changed

- Cleanup code and add new features by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/17

#### Added

- added `getTableFields(?string $tableName = null)` method that returns non-appended and non-virtual fields from the passed table.
- 
- added `getTableHeadColAttributes()`, which should return a ComponentAttribute bag. These attributes are added to all tds in the table head.
- 
- is null and is not null operator for filters
- 

#### Changed

- Use methods to pass properties to frontend
- `getColLabels()`
- `getSortable()`
- `getAggregatable()`
- `getIsSearchable()`
- 
- renamed `searchable` to `isSearchable`
- 
- remamed `selectable` to `isSelectable`
- 
- `isSearchable` is now passed within the `render` method to the frontend
- 
- `getFormatters()` now returns the array of formatters
- 
- renamed `loadFields()` to `getFilterableColumn()`
- 
- money formatter now adds text color in dependence if the value is positive or negative
- 

#### Removed

- removed `colLabels` property
- 
- removed hydration of `colLabels`, `sortable`, `aggregatable`, `isSearchable`, `formatters` from mount method
- 
- removed `stretchCol` property, use `getTableHeadColAttributes()` instead
- 
- removed loadash dependency
- 

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.3.4...v0.4.0

## v0.3.4 - 2023-02-22

### What's Changed

- Tailwind Linter by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/14
- ### Added by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/15
- 
- 
- 
- Add methods by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/16

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.3.3...v0.3.4

## v0.3.3 - 2023-02-14

### What's Changed

- fix filter col width by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/13

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.3.2...v0.3.3

## v0.3.2 - 2023-02-14

### What's Changed

- add appending (see README) by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/12

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.3.1...v0.3.2
