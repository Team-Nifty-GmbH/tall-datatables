# Changelog

All notable changes to `tall-datatables` will be documented in this file.

## v0.4.10 - 2023-05-04

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.4.9...v0.4.10

## v0.4.9 - 2023-05-03

### What's Changed

- fix asset not found exception by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/36

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.4.8...v0.4.9

## v0.4.8 - 2023-04-25

### What's Changed

- add filter inputs below column names by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/33
- prevent click event by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/35

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.4.7...v0.4.8

## v0.4.7 - 2023-04-21

### What's Changed

- Add table headline by @nehegeb in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/19
- add fully qualified column name in toEloquentBuilder() by @SirSplasch in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/32

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.4.6...v0.4.7

## v0.4.6 - 2023-03-29

### What's Changed

- re-add built js file by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/31

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.4.5...v0.4.6

## v0.4.5 - 2023-03-29

### What's Changed

- fix bugs by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/29
- remove array_is_list() check by @SirSplasch in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/30

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.4.4...v0.4.5

## v0.4.4 - 2023-03-10

### What's Changed

- enable null filters by @SirSplasch in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/20
- remove array_filter() to not accidentally remove 0 by @SirSplasch in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/21
- Fix table sort order of variable $orderAsc by @nehegeb in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/25
- Make column labels customizable by @nehegeb in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/27

### New Contributors

- @nehegeb made their first contribution in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/25

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.4.3...v0.4.4

## v0.4.3 - 2023-02-26

### What's Changed

- Upgrade laraval 10 by @patrickweh in https://github.com/Team-Nifty-GmbH/tall-datatables/pull/18

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.4.2...v0.4.3

## v0.4.2 - 2023-02-23

**Full Changelog**: https://github.com/Team-Nifty-GmbH/tall-datatables/compare/v0.4.1...v0.4.2

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
- 
- 
- 
- 
- 
- 
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
