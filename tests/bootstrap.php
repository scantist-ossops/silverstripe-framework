<?php
/**
 * The bootstrap file for running unit tests. This assumes that the framework
 * is installed in a standard composer layout, or has an alternate autoloader
 * provided.
 *
 * @package framework
 * @subpackage tests
 */

$base = realpath(__DIR__ . '/../../../..');

// Allow defining a custom application class using an environment variable.
if(!$appClass = getenv('SS_APPLICATION_CLASS')) {
	$appClass = 'Application';
}

// Attempt to load the composer autoloader.
if(file_exists($loader = "$base/vendor/autoload.php")) {
	require_once $loader;
}

if(!class_exists($appClass)) {
	printf("The application class '%s' does not exist.\n", $appClass);
	die(1);
}

// Use the actual application class for testing so we have access to all
// the modules.
$application = new $appClass();
$application->start();

// Fake a request.
$request = RoutedRequest::create_from_cli('/dev');
$request->setGlobals();

$application->getInjector()->registerNamedService('Request', $request);

/**
 * @deprecated 3.1
 */
define('BASE_URL', rtrim($request->getBaseUrl(), '/'));

TestRunner::use_test_manifest();

// Restore the error handler so PHPUnit can add its own.
restore_error_handler();
