Mongofill [![Build Status](https://secure.travis-ci.org/mongofill/mongofill.png)](http://travis-ci.org/mongofill/mongofill)
=========

Pure PHP implementation of MongoDB driver, with aim to be a drop-in
replacement of the official extension, usable under HHVM runtime.

Installation
------------

### Recommended way
The recommended way of installing is through the [mongofill-hhvm](https://github.com/mongofill/mongofill-hhvm) package as HNI extension of HHVM

```bash
git clone https://github.com/mongofill/mongofill-hhvm
cd mongofill-hhvm
./build.sh
```

You can read the full instructions at [building-and-installation instructions](https://github.com/mongofill/mongofill-hhvm#building-and-installation) from the HNI package.

### Easy way
The easy way of installing Mongofill is [through composer](http://getcomposer.org).
You can see [package information on Packagist.](https://packagist.org/packages/mongofill/mongofill)

```JSON
{
    "require": {
        "mongofill/mongofill": "dev-master"
    }
}
```

> Note: as pure PHP, the phpversion('mongo') will return null and some libraries as Doctrine will not work properly [without modifications](https://github.com/mcuadros/mongodb-odm/commit/b89b21b8dca6a0b545a718f3805248453a27ec3d), so please use the HNI version.



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


Caveats
-------

### Replica Sets & APC

When using a replica set, the Mongofill driver needs to fetch information about the replica set configuration and status. To improve performance, the driver will attempt to use APC (apc_fetch, apc_store) to cache replica set data.

The APC functions should be automatically installed with HHVM, but if you plan on using the Mongofill driver with PHP, you'll need to make sure APC is installed if you want the performance boost.

For PHP 5.5 and higher, this would be the APCu extension. If APC is not installed, replica sets will still function, they just won't be as fast.
