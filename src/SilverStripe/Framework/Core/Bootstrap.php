<?php
/**
 * @package framework
 * @subpackage core
 */

namespace SilverStripe\Framework\Core;

/**
 * Performs misc. global bootstrapping activities.
 *
 * @package framework
 * @subpackage core
 */
class Bootstrap {

	private static $bootstrapped = false;

	/**
	 * The main bootstrap function. This can be called several times but will
	 * only perform operations once.
	 */
	public static function bootstrap(ApplicationInterface $app) {
		if(self::$bootstrapped) {
			return;
		}

		// Report all errors by default, unless the site is in live mode, in
		// which case the error reporting level is decreased later
		error_reporting(-1);

		// Include environment files
		$base = $app->getBasePath();

		for($i = 0; $i <= 3; $i++) {
			$path = $base . '/' . str_repeat('../', $i) . '_ss_environment.php';

			if(@file_exists($path)) {
				define('SS_ENVIRONMENT_FILE', $path);
				include_once $path;
				break;
			}
		}

		if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
			if($_REQUEST) stripslashes_recursively($_REQUEST);
			if($_GET) stripslashes_recursively($_GET);
			if($_POST) stripslashes_recursively($_POST);
			if($_COOKIE) stripslashes_recursively($_COOKIE);
		}

		define('BASE_PATH', $app->getBasePath());

		define('FRAMEWORK_DIR', 'framework');
		define('FRAMEWORK_PATH', $app->getModule('framework')->getPath());
		define('FRAMEWORK_ADMIN_DIR', 'framework/admin');
		define('FRAMEWORK_ADMIN_PATH', FRAMEWORK_PATH . '/admin');

		define('SAPPHIRE_DIR', FRAMEWORK_DIR);
		define('SAPPHIRE_PATH', FRAMEWORK_PATH);
		define('SAPPHIRE_ADMIN_DIR', FRAMEWORK_ADMIN_DIR);
		define('SAPPHIRE_ADMIN_PATH', FRAMEWORK_ADMIN_PATH);

		define('THIRDPARTY_DIR', FRAMEWORK_DIR . '/thirdparty');
		define('THIRDPARTY_PATH', FRAMEWORK_PATH . '/thirdparty');
		define('ASSETS_DIR', 'assets');
		define('ASSETS_PATH', $app->getAssetsPath());

		/**
		 * @deprecated 3.1
		 */
		define('THEMES_DIR', 'themes');
		define('THEMES_PATH', BASE_PATH . '/' . THEMES_DIR);


		if(!defined('TEMP_FOLDER')) {
			define('TEMP_FOLDER', $app->getTempPath());
		}

		// Define template priorities
		define('PR_HIGH', 100);
		define('PR_MEDIUM', 50);
		define('PR_LOW', 10);

		// Ensure we have enough memory
		increase_memory_limit_to('64M');

		// Ensure we don't run into xdebug's fairly conservative infinite
		// recursion protection limit
		increase_xdebug_nesting_level_to(200);

		// Set default encoding
		mb_http_output('UTF-8');
		mb_internal_encoding('UTF-8');
		mb_regex_encoding('UTF-8');

		// Enable better garbage collection
		gc_enable();

		// Set the include path
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

		self::$bootstrapped = true;
	}

	/**
	 * @ignore
	 */
	private function __construct() {}

}
