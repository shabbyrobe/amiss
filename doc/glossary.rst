Glossary
========

.. glossary::

    callback
        php.net/manual/en/language.pseudo-types.php#language.types.callback
    
    PSR-0
        Standard for namespacing and class organisation for Autoloading. 
        <See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md>

    2-tuple
        A :term:`tuple` that has exactly 2 elements, one at index ``0``, the other at index ``1``.

    tuple
        This is a term borrowed from Python. In Python, a tuple is an immutable (unchangeable) list.
        This is represented in PHP using a simple integer indexed array and trying to be disciplined
        enough not to change it: ``array('a', 'b', 'c')``.

        Tuples may be referred to as having a fixed number of elements. A :term:`2-tuple` is a tuple
        that always contains exactly two elements.

        Some functions have return types that are referred to as **n**-tuples. This can pair nicely
        with the PHP `list() <http://php.net/list>`_ builtin:

        .. code-block:: php
            
            <?php
            function make_me_a_2tuple() {
                return array('a', 'b');
            }
            list($a, $b) = make_me_a_2tuple();
