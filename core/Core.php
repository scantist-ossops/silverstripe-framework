<?php
/**
 * This file is the Framework bootstrap.  It will get your environment ready to call Director::direct().
 *
 * It takes care of:
 *  - Including _ss_environment.php
 *  - Normalisation of $_SERVER values
 *  - Initialisation of necessary constants (mostly paths)
 *  - Checking of PHP memory limit
 *  - Including all the files needed to get the manifest built
 *  - Building and including the manifest
 * 
 * Initialized constants:
 * - BASE_URL: Full URL to the webroot, e.g. "http://my-host.com/my-webroot" (no trailing slash).
 * - BASE_PATH: Absolute path to the webroot, e.g. "/var/www/my-webroot" (no trailing slash).
 *   See Director::baseFolder(). Can be overwritten by Director::setBaseFolder().
 * - TEMP_FOLDER: Absolute path to temporary folder, used for manifest and template caches. Example: "/var/tmp"
 *   See getTempFolder(). No trailing slash.
 * - MODULES_DIR: Not used at the moment
 * - MODULES_PATH: Not used at the moment
 * - THEMES_DIR: Path relative to webroot, e.g. "themes"
 * - THEMES_PATH: Absolute filepath, e.g. "/var/www/my-webroot/themes"
 * - FRAMEWORK_DIR: Path relative to webroot, e.g. "framework"
 * - FRAMEWORK_PATH:Absolute filepath, e.g. "/var/www/my-webroot/framework"
 * - FRAMEWORK_ADMIN_DIR: Path relative to webroot, e.g. "framework/admin"
 * - FRAMEWORK_ADMIN_PATH: Absolute filepath, e.g. "/var/www/my-webroot/framework/admin"
 * - THIRDPARTY_DIR: Path relative to webroot, e.g. "framework/thirdparty"
 * - THIRDPARTY_PATH: Absolute filepath, e.g. "/var/www/my-webroot/framework/thirdparty"
 * 
 * @todo This file currently contains a lot of bits and pieces, and its various responsibilities should probably be
 * moved into different subsystems.
 * @todo A lot of this stuff is very order-independent; for example, the require_once calls have to happen after the defines.'
 * This could be decoupled.
 * @package framework
 * @subpackage core
 */

///////////////////////////////////////////////////////////////////////////////
// ENVIRONMENT CONFIG

// ALL errors are reported, including E_STRICT by default *unless* the site is in
// live mode, where reporting is limited to fatal errors and warnings (see later in this file)
error_reporting(E_ALL | E_STRICT);

/**
 * Include _ss_environment.php files
 */
$envFiles = array('_ss_environment.php', '../_ss_environment.php', '../../_ss_environment.php', '../../../_ss_environment.php');
foreach($envFiles as $envFile) {
	if(@file_exists($envFile)) {
		define('SS_ENVIRONMENT_FILE', $envFile);
		include_once($envFile);
		break;
	}
}

///////////////////////////////////////////////////////////////////////////////
// GLOBALS AND DEFINE SETTING

if(isset($_SERVER['HTTP_HOST'])) {
	/**
	 * Fix magic quotes setting
	 */
	if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
		if($_REQUEST) stripslashes_recursively($_REQUEST);
		if($_GET) stripslashes_recursively($_GET);
		if($_POST) stripslashes_recursively($_POST);
	}
}

/**
 * Define system paths
 */
if(!defined('BASE_PATH')) {
	// Assuming that this file is framework/core/Core.php we can then determine the base path
	$candidateBasePath = rtrim(dirname(dirname(dirname(__FILE__))), DIRECTORY_SEPARATOR);
	// We can't have an empty BASE_PATH.  Making it / means that double-slashes occur in places but that's benign.
	// This likely only happens on chrooted environemnts
	if($candidateBasePath == '') $candidateBasePath = DIRECTORY_SEPARATOR;
	define('BASE_PATH', $candidateBasePath);
}

define('MODULES_DIR', 'modules');
define('MODULES_PATH', BASE_PATH . '/' . MODULES_DIR);
define('THEMES_DIR', 'themes');
define('THEMES_PATH', BASE_PATH . '/' . THEMES_DIR);
// Relies on this being in a subdir of the framework.
// If it isn't, or is symlinked to a folder with a different name, you must define FRAMEWORK_DIR
if(!defined('FRAMEWORK_DIR')) {
	define('FRAMEWORK_DIR', basename(dirname(dirname(__FILE__))));
}
define('FRAMEWORK_PATH', BASE_PATH . '/' . FRAMEWORK_DIR);
define('FRAMEWORK_ADMIN_DIR', FRAMEWORK_DIR . '/admin');
define('FRAMEWORK_ADMIN_PATH', BASE_PATH . '/' . FRAMEWORK_ADMIN_DIR);

// These are all deprecated. Use the FRAMEWORK_ versions instead.
define('SAPPHIRE_DIR', FRAMEWORK_DIR);
define('SAPPHIRE_PATH', FRAMEWORK_PATH);
define('SAPPHIRE_ADMIN_DIR', FRAMEWORK_ADMIN_DIR);
define('SAPPHIRE_ADMIN_PATH', FRAMEWORK_ADMIN_PATH);

define('THIRDPARTY_DIR', FRAMEWORK_DIR . '/thirdparty');
define('THIRDPARTY_PATH', BASE_PATH . '/' . THIRDPARTY_DIR);
define('ASSETS_DIR', 'assets');
define('ASSETS_PATH', BASE_PATH . '/' . ASSETS_DIR);

/**
 * Define the temporary folder if it wasn't defined yet
 */
if(!defined('TEMP_FOLDER')) {
	define('TEMP_FOLDER', getTempFolder());
}

/**
 * Priorities definition. These constants are used in calls to _t() as an optional argument
 */
define('PR_HIGH',100);
define('PR_MEDIUM',50);
define('PR_LOW',10);

/**
 * Ensure we have enough memory
 */
increase_memory_limit_to('64M');

/**
 * Ensure we don't run into xdebug's fairly conservative infinite recursion protection limit
 */
increase_xdebug_nesting_level_to(200);

/**
 * Set default encoding
 */
mb_http_output('UTF-8');
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');

/**
 * Enable better garbage collection
 */
gc_enable();

///////////////////////////////////////////////////////////////////////////////
// INCLUDES

if(defined('CUSTOM_INCLUDE_PATH')) {
	$includePath = CUSTOM_INCLUDE_PATH . PATH_SEPARATOR
		. FRAMEWORK_PATH . PATH_SEPARATOR
		. FRAMEWORK_PATH . '/parsers' . PATH_SEPARATOR
		. THIRDPARTY_PATH . PATH_SEPARATOR
		. get_include_path();
} else {
	$includePath = FRAMEWORK_PATH . PATH_SEPARATOR
		. FRAMEWORK_PATH . '/parsers' . PATH_SEPARATOR
		. THIRDPARTY_PATH . PATH_SEPARATOR
		. get_include_path();
}

set_include_path($includePath);

// Include the files needed the initial manifest building, as well as any files
// that are needed for the boostrap process on every request.
require_once 'cache/Cache.php';
require_once 'core/Object.php';
require_once 'core/ClassInfo.php';
require_once 'view/TemplateGlobalProvider.php';
require_once 'control/Director.php';
require_once 'dev/Debug.php';
require_once 'dev/DebugView.php';
require_once 'dev/Backtrace.php';
require_once 'dev/ZendLog.php';
require_once 'dev/Log.php';
require_once 'filesystem/FileFinder.php';
require_once 'core/manifest/ClassLoader.php';
require_once 'core/manifest/ClassManifest.php';
require_once 'core/manifest/ManifestFileFinder.php';
require_once 'core/manifest/TemplateLoader.php';
require_once 'core/manifest/TemplateManifest.php';
require_once 'core/manifest/TokenisedRegularExpression.php';
require_once 'control/injector/Injector.php';

// Initialise the dependency injector as soon as possible, as it is 
// subsequently used by some of the following code
$default_options = array('locator' => 'SilverStripeServiceConfigurationLocator');
Injector::inst($default_options); 

///////////////////////////////////////////////////////////////////////////////
// MANIFEST

// Regenerate the manifest if ?flush is set, or if the database is being built.
// The coupling is a hack, but it removes an annoying bug where new classes
// referenced in _config.php files can be referenced during the build process.
$flush = isset($_GET['flush']);
$manifest = new SS_ClassManifest(BASE_PATH, false, $flush);

$loader = SS_ClassLoader::instance();
$loader->registerAutoloader();
$loader->pushManifest($manifest);

// Now that the class manifest is up, load the configuration
$configManifest = new SS_ConfigManifest(BASE_PATH, false, $flush);
Config::inst()->pushConfigManifest($configManifest);

SS_TemplateLoader::instance()->pushManifest(new SS_TemplateManifest(
	BASE_PATH, false, isset($_GET['flush'])
));

// If in live mode, ensure deprecation, strict and notices are not reported
if(Director::isLive()) {
	error_reporting(E_ALL & ~(E_DEPRECATED | E_STRICT | E_NOTICE));
}

///////////////////////////////////////////////////////////////////////////////
// POST-MANIFEST COMMANDS

/**
 * Load error handlers
 */
Debug::loadErrorHandlers();
