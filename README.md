[![Build Status](https://secure.travis-ci.org/koubas/mongofill.png)](http://travis-ci.org/koubas/mongofill)


Mongofill
=========

Pure PHP implementation of MongoDB driver, with aim to be a drop-in
replacement of the official extension, usable under HHVM runtime.


Development
===========

Please push tests, ensuring compatibility with the official Mongo extension,
that are not passing yet, into the  "compat/not-passing" branch.

Contributions are greatly appreciated, including corrections of my english ;)


Contributing
===========

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
cd test/native/
./helper.sh setup
./helper.sh boot
./helper.sh run
```
