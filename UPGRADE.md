Upgrading
=========

v2.0.x to v3.0
--------------

New features:

- Added encoder field type. This allows automatic PHP serialization or json_encoding of 
  data in the mapper.
- Added support for embedding objects

API changes:

- `Amiss\Manager->getByPk` has been renamed `getById`
- `Amiss\Manager->deleteByPk` has been renamed `deleteById`
- `Amiss\Mapper\Note` now only takes a cache object as its first argument, it no longer
  supports a 2-tuple of closures.
- `Amiss\Loader` is no longer a generic loader. It cannot be used for other PSR-0 loading.
