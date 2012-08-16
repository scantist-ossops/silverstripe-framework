<?php
/**
 * An entry point for legacy-style applications where modules are installed in
 * the webroot, and the framework module is not installed via composer.
 *
 * @package framework
 */

use SilverStripe\Framework\WebrootApplication;

$base = dirname(__DIR__);
$loader = "$base/vendor/autoload.php";

if(!file_exists($loader)) {
	echo "The SilverStripe dependencies have not been installed. Please " .
	     "install the dependencies using Composer in the root of your " .
	     "project. See the installation documentation for more information. " .
	     "The `silverstripe/framework-deps` meta-package will install the " .
	     "required dependencies.";
	exit(1);
}

require_once __DIR__ . '/src/SilverStripe/Framework/functions.php';

$loader = require_once $loader;
$loader->add('SilverStripe\\Framework', __DIR__ . '/src');

WebrootApplication::respond();
