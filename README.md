Mongofill [![Build Status](https://secure.travis-ci.org/koubas/mongofill.png)](http://travis-ci.org/koubas/mongofill)
=========

Pure PHP implementation of MongoDB driver, with aim to be a drop-in
replacement of the official extension, usable under HHVM runtime.


Supported libraries
-------------------

You can check the current supported libraries at wiki page [Supported-Libraries](https://github.com/koubas/mongofill/wiki/Supported-Libraries)


Community
---------

You can catch us on IRC on Freenode channel #mongofill


Contributing
---------

Please push tests, ensuring compatibility with the official Mongo extension,
that are not passing yet, into the  "compat/not-passing" branch.

Contributions are greatly appreciated, including corrections of our english ;)

To ensure a consistent code base, you should make sure the code follows the PSR2 coding standards. We suggest use php-cs-fixer with your code before make the pull request with this flags: `php-cs-fixer fix . --level=all`

### Running the PHPUnit tests

Tests are in the `test` folder.
To run them, you need PHPUnit.

``` bash
phpunit --configuration phpunit.xml.dist
```

### Running the native mongo-php-driver tests

You can find helper script at `test/native/helper.sh`. Your system must have installed: mongodb, git, phpize and autotools

``` bash
cd tests/native/
./helper.sh setup
./helper.sh boot
./helper.sh run
```


Benchmarking
---------

A small suite of benchmarking is included with the package, you can run the suite with this command:

``` bash
php ./vendor/bin/athletic -b tests/bootstrap.php  -p tests/Mongofill/Benchmarks/
```

Some results can be find at: https://gist.github.com/mcuadros/9551290

