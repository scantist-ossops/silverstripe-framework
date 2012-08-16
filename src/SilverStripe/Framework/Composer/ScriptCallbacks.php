<?php
/**
 * @package framework
 * @subpackage composer
 */

namespace SilverStripe\Framework\Composer;

use DatabaseAdmin;
use RoutedRequest;
use SilverStripe\Framework\Core\ModuleSet;
use SilverStripe\Framework\Filesystem\AssetsInstaller;
use Symfony\Component\Yaml\Yaml;

/**
 * Provides callback functions for composer scripts.
 *
 * @package framework
 * @subpackage composer
 */
class ScriptCallbacks {

	const MODULES_FILE = 'modules.yml';

	public static $default_options = array(
		'silverstripe-application-dir' => 'application',
		'silverstripe-application-class' => 'Application',
		'silverstripe-modules-file' => 'modules.yml'
	);

	public static $package_types = array(
		'silverstripe-module' => ModuleSet::TYPE_MODULE,
		'silverstripe-widget' => ModuleSet::TYPE_WIDGET,
		'silverstripe-theme' => ModuleSet::TYPE_THEME
	);

	/**
	 * Updates the yaml file registering modules installed in the application.
	 */
	public static function update_modules($event) {
		$opts = self::get_options($event);
		$app = self::get_application($event);

		$modules = array();
		$path = $app->getBasePath() . '/' . $opts['silverstripe-application-dir'];
		$file = $path . '/' . $opts['silverstripe-modules-file'];

		if(!is_dir($path)) {
			throw new \Exception(sprintf(
				'The application path "%s" does not exist', $path
			));
		}

		$repo = $event->getComposer()->getRepositoryManager()->getLocalRepository();
		$packages = $repo->getPackages();

		foreach($packages as $package) {
			if(array_key_exists($package->getType(), self::$package_types)) {
				$name = $package->getName();
				$name = substr($name, strrpos($name, '/') + 1);
				$extra = $package->getExtra();

				if(isset($extra['silverstripe-module-class'])) {
					$modules[] = array(
						'class' => $extra['silverstripe-module-class']
					);
				} else {
					$modules[] = array(
						'name' => $name,
						'type' => self::$package_types[$package->getType()],
						'path' => "vendor/{$package->getPrettyName()}"
					);
				}
			}
		}

		file_put_contents($file, Yaml::dump(
			array('modules' => $modules), 3
		));
	}

	public static function install_assets($event) {
		$installer = new AssetsInstaller();
		$installer->setApplication(self::get_application($event));
		$installer->install();
	}

	public static function build_database($event) {
		require_once __DIR__ . '/../functions.php';

		$app = self::get_application($event);
		$app->start();

		$request = RoutedRequest::create();
		$app->getInjector()->registerNamedService('Request', $request);

		$builder = new DatabaseAdmin();
		$builder->doBuild(!$event->getIO()->isVerbose());

		$app->stop();
	}

	protected static function get_options($event) {
		return self::$default_options + $event->getComposer()->getPackage()->getExtra();
	}

	/**
	 * @return \SilverStripe\Framework\Core\ApplicationInterface
	 */
	protected static function get_application($event) {
		$opts = self::get_options($event);
		$class = $opts['silverstripe-application-class'];

		if(!class_exists($class)) {
			throw new \Exception(sprintf(
				'The application class "%s" does not exist, assets not installed',
				$class
			));
		}

		return new $class();
	}

}
