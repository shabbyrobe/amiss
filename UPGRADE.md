Upgrading
=========

v2.0.x to v3.0
--------------

New features:

- Added encoder field type. This allows automatic PHP serialization or json_encoding of 
  data in the mapper.
- Added support for embedding objects.
- Added simple MongoDB support

Breaking changes:

- `Amiss\Mapper\Note` no longer adds any types by default - to get the default set from previous
  versions, create it like so: `$mapper = (new Amiss\Mapper\Note())->addTypeSet(new Amiss\Sql\TypeSet);`
- `Amiss\Manager` has been renamed `Amiss\Sql\Manager`
- `Amiss\Sql\Manager->getByPk` has been renamed `getById`
- `Amiss\Sql\Manager->deleteByPk` has been renamed `deleteById`
- `\Amiss\Sql\Mapper->exportRow` has been renamed `fromObject`
- `\Amiss\Sql\Mapper->buildObject` has been renamed `toObject`
- `Amiss\Mapper\Note` now only takes an instance of `Amiss\Cache` as its first argument, it no longer
  supports a 2-tuple of closures.
- `Amiss\Loader` is no longer a generic loader. It cannot be used for other PSR-0 loading.
