PHPUnit_SkeletonGenerator
=========================

`phpunit-skelgen` is a tool that can generate skeleton test classes from production code classes and vice versa.

Installation
------------

PHPUnit_SkeletonGenerator should be installed using the PEAR Installer, the backbone of the [PHP Extension and Application Repository](http://pear.php.net/) that provides a distribution system for PHP packages.

Depending on your OS distribution and/or your PHP environment, you may need to install PEAR or update your existing PEAR installation before you can proceed with the following instructions. `sudo pear upgrade PEAR` usually suffices to upgrade an existing PEAR installation. The [PEAR Manual ](http://pear.php.net/manual/en/installation.getting.php) explains how to perform a fresh installation of PEAR.

The following two commands (which you may have to run as `root`) are all that is required to install PHPUnit_SkeletonGenerator using the PEAR Installer:

    pear config-set auto_discover 1
    pear install pear.phpunit.de/PHPUnit_SkeletonGenerator

After the installation you can find the PHPUnit_SkeletonGenerator source files inside your local PEAR directory; the path is usually `/usr/lib/php/PHPUnit`.
