Upgrading
=========

v2.0.x to v3.0
--------------

New features:



`Amiss\Sql\Manager` renamed methods: 

- `getByPk` has been renamed with `getById`
- `deleteByPk` has been renamed with `deleteById`

`Amiss\Mapper` renamed methods:

- `exportRow` renamed to `fromObject`
- `buildObject` renamed to `toObject`

Minor changes:

- Added xcache support to base mapper
- `Amiss\Loader` is no longer a generic loader. It cannot be used for other PSR-0 loading.
