<?php
/**
 * @package framework
 * @subpackage injector
 */

namespace SilverStripe\Framework\Injector;

use ClassInfo;
use SilverStripe\Framework\Core\Application;

/**
 * Locates service configuration from an application.
 *
 * @package framework
 * @subpackage injector
 */
class ApplicationServiceConfigurationLocator implements ServiceConfigurationLocatorInterface {

	protected $app;
	protected $cache = array();

	public function __construct(Application $app) {
		$this->app = $app;
	}

	public function locateConfigFor($name) {
		if(array_key_exists($name, $this->cache)) {
			return $this->cache[$name];
		}

		$inst = $this->app->getConfig();
		$config = $inst->get('Injector', $name);

		if($config) {
			return $this->cache[$name] = $config;
		}

		if(ClassInfo::exists($name)) {
			$classes = array_reverse(array_keys(ClassInfo::ancestry($name)));
			array_shift($classes);

			foreach($classes as $class) {
				if(array_key_exists($class, $this->cache)) {
					return $this->cache[$class];
				}

				$config = $inst->get('Injector', $class);

				if($config) {
					return $this->cache[$class] = $config;
				} else {
					$this->cache[$class] = null;
				}
			}

			$this->cache[$name] = null;
		}
	}

}
