Definition Finder [![Build Status](https://travis-ci.org/fredemmott/definition-finder.svg?branch=master)](https://travis-ci.org/fredemmott/definition-finder)
==================

This library finds all the definitions in a file or tree that HHVM understands. It is used to generate [the Hack reference documentation](http://docs.hhvm.com/hack/reference/), and be used for other purposes such as [generating autoload maps](https://github.com/fredemmott/hhvm-autoload-map-generator/)

This project requires HHVM 3.9 or later - however, if the code being scanned requires
a later version of HHVM, definition-finder may not be able to parse it on the lower
version.

Usage
-----

There are three main entrypoints:

 - [`FileParser::FromFile(string $filename)`](src/FileParser.php)
 - [`FileParser::FromData(string $data, ?string $filename = null)`](src/FileParser.php)
 - [`TreeParser::FromPath(string $path)`](src/TreeParser.php)

`FileParser` returns definitions from a single file, whereas `TreeParser` recurses over an entire directory tree. All 3 of these functions return an implementation of [`BaseParser`](src/BaseParser.php). There are three forms of accessors:

 - `getClasses(): \ConstVector<ScannedBasicClass>` - returns a `ConstVector` of [`ScannedBasicClass`](src/definitions/ScannedBasicClass.php], which has a similar interface to `ReflectionClass`
 - `getClassNames(): \ConstVector<string>` - returns a `ConstVector` of class names
 - `getClass(string $name): ScannedBasicClass` - returns a `ScannedBasicClass` for the specified class, or throws an exception if it was not found

Similar functions exist for interfaces, traits, constants, enums, and typedefs.

Installation
------------

```
hhvm composer require fredemmott/definition-finder
```

Status
------

The API is stable, and this is used in production to generate [the Hack reference documentation](http://docs.hhvm.com/hack/reference/).

It has been used extensively on modern Hack and PHP code, including all of the Hack typechecker definitions and extensions inside HHVM; it has not yet been extensively used on older code bases - please file an issue if you find PHP code that it can not handle.

Implementation
--------------

This is a recursive parser built on the token stream exposed by `token_get_all()`.

Contributing
============

We welcome GitHub issues and pull requests - please see CONTRIBUTING.md for details.

License
=======

definition-finder is [BSD-licensed](LICENSE). We also provide an additional [patent grant](PATENTS).
