Amiss - Stupid, Simple, Fast!
=============================

Amiss is a PHP Data Mapper and Active Record implementation for MySQL.

Amiss requires PHP 5.3 and recommends using MySQL, though it does seem to support SQLite reasonably well and should perform OK with anything largely ANSI-compliant and PDO-compatible. It has not been tested with anything else.

Amiss does not try to be a fully-fledged ORM that cooks you breakfast, walks the dog and gives your schema its daily back massage, it only tries to take the monotony and repetition out of retrieving simple objects and simple relationships from a database that already exists.

It was written primarily to quickly replace a hacky ORM layer that never lived up to its promise with something a bit more efficient in a very short space of time, and proved to be quite handy for quick-n-dirty data mapping. I decided to clean the code up and use it as an exercise in turning a useful collection of code scraps into a well documented, well tested package.

I don't recommend using it for anything, ever. Having said that, its brutal simplicity may make it a better candidate for your next throwaway project or prototype than PHP's premier ORM behemoth `Doctrine <http://doctrine-project.org>`_, the dated interface of Propel, or the tight coupling of your favourite framework's own model layer.

It is unapologetic about being `stupid, simple and fast`, and is completely aware that something is **Amiss**.


Documentation
-------------

.. toctree::
    :maxdepth: 1
    
    intro
    configuring
    mapper/index
    active/index
    development


License
-------

Amiss is licensed under the MIT License:

.. literalinclude:: ../LICENSE
