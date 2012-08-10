<?php
/**
 * @package framework
 * @subpackage composer
 */

namespace SilverStripe\Framework\Composer;

use SilverStripe\Framework\Filesystem\AssetsInstaller;

/**
 * Provides callback functions for composer scripts.
 *
 * @package framework
 * @subpackage composer
 */
class ScriptCallbacks {

	public static $default_options = array(
		'silverstripe-application-class' => 'Application'
	);

	public static function install_assets($event) {
		$opts = self::get_options($event);
		$class = $opts['silverstripe-application-class'];

		if(!class_exists($class)) {
			$event->getIO()->write(sprintf(
				'The application class "%s" does not exist, assets not installed',
				$class
			));
		}

		$installer = new AssetsInstaller();
		$installer->setApplication(new $class());
		$installer->install();
	}

	protected static function get_options($event) {
		return self::$default_options + $event->getComposer()->getPackage()->getExtra();
	}

}
