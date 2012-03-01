Data Mapper
===========

From `P of EAA <http://martinfowler.com/eaaCatalog/dataMapper.html>`_:
An object that wraps a row in a database table or view, encapsulates the database access, and adds domain logic on that data.

Amiss contains a very simple approximation of a Data Mapper. It will allow you to map objects to and from a database while keeping your model objects free of any persistence-specific code (well, most of the time, anyway. Some pragmatic decisions have been made at times that may not completely gel with purist expectations).


.. toctree::
    :maxdepth: 2

    quickstart
    connecting
    mapping
    selecting
    relations
    modifying
    helpers
