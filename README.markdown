# PHPUnit_SkeletonGenerator

`phpunit-skelgen` is a tool that can generate skeleton test classes from production code classes and vice versa.

## Installation

There a two supported ways of installing PHPUnit_SkeletonGenerator.

You can use the [PEAR Installer](http://pear.php.net/manual/en/guide.users.commandline.cli.php) to download and install PHPUnit_SkeletonGenerator as well as its dependencies. You can also download a [PHP Archive (PHAR)](http://php.net/phar) of PHPUnit_SkeletonGenerator that has all required dependencies of PHPUnit_SkeletonGenerator bundled in a single file.

### PEAR Installer

The following two commands (which you may have to run as `root`) are all that is required to install PHPUnit_SkeletonGenerator using the PEAR Installer:

    pear config-set auto_discover 1
    pear install pear.phpunit.de/PHPUnit_SkeletonGenerator

### PHP Archive (PHAR)

    wget http://pear.phpunit.de/get/phpunit-skelgen.phar
    chmod +x phpunit-skelgen.phar

## Documentation

The skeleton generator is documented in the [PHPUnit Manual](http://www.phpunit.de/manual/current/en/skeleton-generator.html).
