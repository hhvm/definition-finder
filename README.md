Definition Finder
==================

This library lists all the definitions in a file that HHVM understands. It is
useful for building autoloaders. HH\autoload_set_paths() is also usually faster than an autoloader function.

As of 2015-02-20, the main advantage of building your own autoloader instead of
using composer is that HHVM supported autoloading more than just classes.

Installation
------------

Add `fredemmott/definition-finder` to your Composer `requires` section


Status
------

Work in progress.

**API IS NOT YET STABLE**

License
-------

definition-finder is [BSD-licensed](LICENSE). We also provide an additional [patent grant](PATENTS).
