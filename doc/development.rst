Development
===========

Amiss' homepage is http://k3jw.com/code/amiss/

The code for Amiss is hosted at http://github.com/shabbyrobe/amiss


Running the tests
-----------------

PHPUnit Tests are in the ``tests/`` directory of the distribution. Do not use
the standard ``phpunit`` command to run the tests - Amiss requires a heavily
customised runner.  Instead, run::

    cd /path/to/amiss/
    php test/run.php


There may be some tests that are known to report incorrect results. These can be
excluded by running the tests like so::

    php test/run.php --exclude-group faulty


The runner supports the following PHPUnit arguments:

- ``--exclude-group=group1[,group2]``
- ``--group=group1[,group2]``
- ``--coverage-html=/path/to/reports``

And the following additional arguments:

- ``--no-sqlite``: Exclude the SQLite acceptance tests
- ``--with=<extras>``: Include other acceptance tests using other db engines
  (though you'll need to configure ``amisstestrc``, see below). ``<extras>`` is a
  comma separated list containing one or more of the following: ``mysql``,
  ``mysqlp``


MySQL testing
~~~~~~~~~~~~~

All of the tests in the ``test/acceptance`` directory should be runnable on both
MySQL and SQLite.  Patches won't be accepted unless all tests not marked as
``@group faulty``, ``@group faulty-sqlite`` or ``@group faulty-mysql`` pass.

Amiss will create a dummy database made up of ``amiss_test_`` and the current
timestamp on your MySQL server in the ``setUp`` method, and should drop it in
the ``tearDown``. If it does not, you can drop it by hand safely, and it'd be
nice if you raised an issue with as much information as you have.

The tester needs to know where your server is and what username and password to
use. I strongly recommend creating a dedicated user for this job. It will need
``DROP`` and ``CREATE`` privileges, but this should be limited to only databases
that start with ``amiss_test_``::

    grant all on `amiss\_test\_%`.* to 'amisstester'@'localhost' identified by 'password';

You can then create a file called ``.amisstestrc`` in your home folder. It
should be an ini file with a ``[mysql]`` section, set out like so::

    [mysql]
    host = localhost
    user = amisstester
    password = password

You should then be able to run the MySQL tests. Pass ``mysql`` (normal MySQL)
and/or ``mysqlp`` (MySQL with persistent connections) to the ``--with``
argument, or just pass ``--all``::

    php test/run.php --with=mysql,mysqlp
    php test/run.php --with=all


Building the docs
-----------------

The source distribution does not include compiled documentation, only
restructuredText files. If you need HTML or PDF documentation, it needs to be
built.

If you are using OS X, the documentation build requires the following packages
be available on your machine:

* Macports
* texlive
* texlive-latex-extras
* py2x-sphinx

Linux may require a different set of packages to be installed, and if you're on
Windows, might I be so brave as to suggest VirtualBox? Or just download the
pre-packaged docs from the Amiss website. I will get around to documenting
windows builds as eventually there will be a .chm file too.

Once the above dependencies are met, you can run the following commands (Linux
or OS X)::

    cd /path/to/amiss/doc/
    make html
    make latexpdf

If you are already reading this in HTML or PDF format and feel the urge to
smugly remark "well I'm *already* reading it in HTML so nerny nerny nerrrrr",
here is a medal::

           _______________
          |@@@@|     |####|
          |@@@@|     |####|
          |@@@@|     |####|
          \@@@@|     |####/
           \@@@|     |###/
            `@@|_____|##'
                 (O)
              .-'''''-.
            .'  * * *  `.
           :  *       *  :
          : ~ A S C I I ~ :
          : ~ A W A R D ~ :
           :  *       *  :
      jgs   `.  * * *  .'
              `-.....-' 

Thanks, "jgs". Your ASCII art fills me with gladness.

