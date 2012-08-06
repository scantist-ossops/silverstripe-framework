<?php
/**
 * Contains various framework functions not contained in classes.
 *
 * @package framework
 */

use SilverStripe\Framework\Core\Application;
use SilverStripe\Framework\Dev\Deprecation;

function getSysTempDir() {
	Deprecation::notice(3.0, 'Please use PHP function get_sys_temp_dir() instead.');
	return sys_get_temp_dir();
}

/**
 * Returns the temporary folder that silverstripe should use for its cache files
 * This is loaded into the TEMP_FOLDER define on start up
 *
 * @param $base The base path to use as the basis for the temp folder name.  Defaults to BASE_PATH,
 * which is usually fine; however, the $base argument can be used to help test.
 */
function getTempFolder($base = null) {
	if(!$base) $base = BASE_PATH;

	if($base) {
		$cachefolder = "silverstripe-cache" . str_replace(array(' ', "/", ":", "\\"), "-", $base);
	} else {
		$cachefolder = "silverstripe-cache";
	}

	$ssTmp = BASE_PATH . "/silverstripe-cache";
	if(@file_exists($ssTmp)) {
		return $ssTmp;
	}

	$sysTmp = sys_get_temp_dir();
	$worked = true;
	$ssTmp = "$sysTmp/$cachefolder";

	if(!@file_exists($ssTmp)) {
		$worked = @mkdir($ssTmp);
	}

	if(!$worked) {
		$ssTmp = BASE_PATH . "/silverstripe-cache";
		$worked = true;
		if(!@file_exists($ssTmp)) {
			$worked = @mkdir($ssTmp);
		}
	}

	if(!$worked) {
		throw new Exception(
			'Permission problem gaining access to a temp folder. ' .
				'Please create a folder named silverstripe-cache in the base folder ' .
				'of the installation and ensure it has the correct permissions'
		);
	}

	return $ssTmp;
}

/**
 * @deprecated 3.0 Please use {@link SS_ClassManifest::getItemPath()}.
 */
function getClassFile($className) {
	Deprecation::notice('3.0', 'Use SS_ClassManifest::getItemPath() instead.');
	return SS_ClassLoader::instance()->getManifest()->getItemPath($className);
}

/**
 * Creates a class instance by the "singleton" design pattern.
 * It will always return the same instance for this class,
 * which can be used for performance reasons and as a simple
 * way to access instance methods which don't rely on instance
 * data (e.g. the custom SilverStripe static handling).
 *
 * @param string $className
 * @return Object
 */
function singleton($className) {
	if($className == "Config") user_error("Don't pass Config to singleton()", E_USER_ERROR);
	if(!isset($className)) user_error("singleton() Called without a class", E_USER_ERROR);
	if(!is_string($className)) user_error("singleton() passed bad class_name: " . var_export($className,true), E_USER_ERROR);
	return Application::curr()->get($className);
}

/**
 * @deprecated 3.1
 */
function project() {
	Deprecation::notice('3.1', 'Use the application module');

	global $project;
	return $project;
}

function stripslashes_recursively(&$array) {
	foreach($array as $k => $v) {
		if(is_array($v)) stripslashes_recursively($array[$k]);
		else $array[$k] = stripslashes($v);
	}
}

/**
 * @see i18n::_t()
 */
function _t($entity, $string = "", $context = "", $injection = "") {
	return i18n::_t($entity, $string, $context, $injection);
}

/**
 * Increase the memory limit to the given level if it's currently too low.
 * Only increases up to the maximum defined in {@link set_increase_memory_limit_max()},
 * and defaults to the 'memory_limit' setting in the PHP configuration.
 *
 * @param A memory limit string, such as "64M".  If omitted, unlimited memory will be set.
 * @return Boolean TRUE indicates a successful change, FALSE a denied change.
 */
function increase_memory_limit_to($memoryLimit = -1) {
	$curLimit = ini_get('memory_limit');

	// Can't go higher than infinite
	if($curLimit == -1 ) return true;

	// Check hard maximums
	$max = get_increase_memory_limit_max();

	if($max && $max != -1 && trANSLATE_MEMSTRING($memoryLimit) > translate_memstring($max)) return false;

	// Increase the memory limit if it's too low
	if($memoryLimit == -1 || translate_memstring($memoryLimit) > translate_memstring($curLimit)) {
		ini_set('memory_limit', $memoryLimit);
	}

	return true;
}

$_increase_memory_limit_max = ini_get('memory_limit');

/**
 * Set the maximum allowed value for {@link increase_memory_limit_to()}.
 * The same result can also be achieved through 'suhosin.memory_limit'
 * if PHP is running with the Suhosin system.
 *
 * @param Memory limit string
 */
function set_increase_memory_limit_max($memoryLimit) {
	global $_increase_memory_limit_max;
	$_increase_memory_limit_max = $memoryLimit;
}

/**
 * @return Memory limit string
 */
function get_increase_memory_limit_max() {
	global $_increase_memory_limit_max;
	return $_increase_memory_limit_max;
}

/**
 * Increases the XDebug parameter max_nesting_level, which limits how deep recursion can go.
 * Only does anything if (a) xdebug is installed and (b) the new limit is higher than the existing limit
 *
 * @param int $limit - The new limit to increase to
 */
function increase_xdebug_nesting_level_to($limit) {
	if (function_exists('xdebug_enable')) {
		$current = ini_get('xdebug.max_nesting_level');
		if ((int)$current < $limit) ini_set('xdebug.max_nesting_level', $limit);
	}
}

/**
 * Turn a memory string, such as 512M into an actual number of bytes.
 *
 * @param A memory limit string, such as "64M"
 */
function translate_memstring($memString) {
	switch(strtolower(substr($memString, -1))) {
		case "k": return round(substr($memString, 0, -1)*1024);
		case "m": return round(substr($memString, 0, -1)*1024*1024);
		case "g": return round(substr($memString, 0, -1)*1024*1024*1024);
		default: return round($memString);
	}
}

/**
 * Increase the time limit of this script. By default, the time will be unlimited.
 * Only works if 'safe_mode' is off in the PHP configuration.
 * Only values up to {@link get_increase_time_limit_max()} are allowed.
 *
 * @param $timeLimit The time limit in seconds.  If omitted, no time limit will be set.
 * @return Boolean TRUE indicates a successful change, FALSE a denied change.
 */
function increase_time_limit_to($timeLimit = null) {
	$max = get_increase_time_limit_max();
	if($max != -1 && $timeLimit > $max) return false;

	if(!ini_get('safe_mode')) {
		if(!$timeLimit) {
			set_time_limit(0);
			return true;
		} else {
			$currTimeLimit = ini_get('max_execution_time');
			// Only increase if its smaller
			if($currTimeLimit && $currTimeLimit < $timeLimit) {
				set_time_limit($timeLimit);
			}
			return true;
		}
	} else {
		return false;
	}
}

$_increase_time_limit_max = -1;

/**
 * Set the maximum allowed value for {@link increase_timeLimit_to()};
 *
 * @param Int Limit in seconds
 */
function set_increase_time_limit_max($timeLimit) {
	global $_increase_time_limit_max;
	$_increase_time_limit_max = $timeLimit;
}

/**
 * @return Int Limit in seconds
 */
function get_increase_time_limit_max() {
	global $_increase_time_limit_max;
	return $_increase_time_limit_max;
}
