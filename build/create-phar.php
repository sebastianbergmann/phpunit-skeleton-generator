<?php
$buildRoot = dirname(__FILE__);
$srcRoot = $buildRoot.'/../src';

$phar = new Phar(
	$buildRoot . "/phpunit-skelgen.phar",
	FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
	"phpunit-skelgen.phar"
);

$phar->setStub($phar->createDefaultStub('phpunit-skelgen'));
