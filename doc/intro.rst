Introduction
============

.. only:: latex

    .. include:: preamble.rst.inc


About the pattern
-----------------

The core of Amiss is the Data Mapper.

From Martin Fowler's `Patterns of Enterprise Application Architecture
<http://martinfowler.com/eaaCatalog/dataMapper.html>`_:

    A [Data Mapper is a] layer of Mappers (473) that moves data between objects
    and a database while keeping them independent of each other and the mapper
    itself.

Amiss makes some small concessions to pragmatism that may offend domain model
purists when you use any of the :doc:`mapping methods <mapper/mapping>` that are
in the core distribution, but overall it does a passable job of keeping its
grubby mitts off your data models considering the small codebase.


About the examples
------------------

Most of the examples contained herein will make use of the schema and model that
accompany the documentation in the `doc/demo`_ subdirectory of the source
distribution. It contains a very simple set of related objects representing a
set of events for a music festival.

There is also a set of examples in the `doc/example`_ folder that will allow you
to click through some scripts that are built on this schema. You should never
expose those scripts over a public web server - they are for development
machines only. The code is disgusting.

To browse the examples, run ``./task examples`` in the root of the Amiss project
then point your browser at http://127.0.0.1:8555/.

.. _`doc/demo`:    https://github.com/shabbyrobe/amiss/blob/master/doc/demo
.. _`doc/example`: https://github.com/shabbyrobe/amiss/blob/master/example


Model Classes
-------------

The model classes referred to in the documentation are available in the
:download:`demo/modeldefs.php` file, and are included in this manual in the
:doc:`docmodels` section.

