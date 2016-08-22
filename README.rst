Amiss - Stupid, Simple, Fast!
=============================

Important update
----------------

I've been working on some pretty major revisions to the mapping and relation
definitions in the "develop" branch under the "5.0" banner. 5.0 is nearly
ready for RC1, I've just run into some trouble making a script to allow
people to automatically convert their old-style mappings into the new style.

If you are starting a new project with Amiss, I recommend using
"5.0.0.beta.4" from composer.

5.0 also has a minor performance regression due to a nasty bug in PHP's
source: https://bugs.php.net/bug.php?id=66350, hopefully this will be fixed
too.

If you have a project which depends on Amiss v4, sit tight. I will continue
to support Amiss v4 with bugfixes and possibly minor feature updates after
5.0 is ready and 5.0 should hopefully (no promises) ship with a script to
automate your annotation updates automatically.


Usual Stuff
-----------

**Amiss** is a Data Mapper and Active Record implementation for MySQL and PHP
5.3 or greater.

**Amiss** does not try to be a fully-fledged ORM that cooks you breakfast, walks
the dog and gives your schema its daily back massage, it only tries to take the
monotony and repetition out of retrieving simple objects and simple
relationships from a database that already exists.

Its not the fanciest ORM on the block, but its brutal simplicity may make it a
better candidate for your next throwaway project or prototype than PHP's premier
ORM behemoth `Doctrine <http://doctrine- project.org>`_, the dated interface of
`Propel <http://www.propelorm.org/>`_, or the tight coupling of your favourite
framework's own model layer.

It is unapologetic about being `stupid, simple and fast`.


Documentation
-------------

See the ``doc/`` folder for more info, or the ``example/`` folder for some
quickstarts. Visit http://k3jw.com/code/amiss/ for HTML docs and PDF downloads.


License
-------

Amiss is licensed under the MIT License. See ``LICENSE`` for more info.

