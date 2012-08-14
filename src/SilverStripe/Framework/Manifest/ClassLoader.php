<?php
/**
 * @package framework
 * @subpackage manifest
 */

namespace SilverStripe\Framework\Manifest;

use SilverStripe\Framework\Core\Application;
use SilverStripe\Framework\Dev\Deprecation;

/**
 * Loads classes, interfaces and traits from a stack of {@link PhpManifest}
 * instances.
 *
 * @package framework
 * @subpackage manifest
 */
class ClassLoader {

	/**
	 * A map of legacy class names to new class names.
	 */
	public static $legacy_classes = array(
		'aftercallaspect' => 'SilverStripe\\Framework\\Injector\\AfterCallAspect',
		'aopproxyservice' => 'SilverStripe\\Framework\\Injector\\',
		'beforecallaspect' => 'SilverStripe\\Framework\\Injector\\',
		'config' => 'SilverStripe\\Framework\\Core\\Config',
		'config_forclass' => 'SilverStripe\\Framework\\Core\\ConfigForClass',
		'cookie' => 'SilverStripe\\Framework\\Http\\Cookie',
		'deprecation' => 'SilverStripe\\Framework\\Dev\\Deprecation',
		'http' => 'SilverStripe\\Framework\\Http\\Http',
		'injector' => 'SilverStripe\\Framework\\Injector\\Injector',
		'ss_classloader' => 'SilverStripe\\Framework\\Manifest\\ClassLoader',
		'ss_dag' => 'SilverStripe\\Framework\\Util\\Dag',
		'ss_filefinder' => 'SilverStripe\\Framework\\Filesystem\\FileFinder',
		'session' => 'SilverStripe\\Framework\\Http\\Session',
		'ss_dag' => 'SilverStripe\\Framework\\Util\\Dag',
		'ss_filefinder' => 'SilverStripe\\Framework\\Filesystem\\FileFinder',
		'ss_httprequest' => 'SilverStripe\\Framework\\Http\\Request',
		'ss_httpresponse' => 'SilverStripe\\Framework\\Http\\Response',
		'ss_httpresponse_exception' => 'SilverStripe\\Framework\\Http\\ResponseException',
		'ss_templateloader' => 'SilverStripe\\Framework\\Manifest\\TemplateLoader'
	);

	protected $manifests = array();

	/**
	 * @deprecated 3.1 Use `Application::curr()->get('ClassLoader')`
	 */
	public static function instance() {
		Deprecation::notice('3.1');
		return Application::curr()->get('ClassLoader');
	}

	public function __construct(PhpManifest $manifest = null) {
		if($manifest) $this->pushManifest($manifest);
	}

	/**
	 * Returns the currently active class manifest instance that is used for
	 * loading items.
	 *
	 * @return PhpManifest
	 */
	public function getManifest() {
		if($this->manifests) return $this->manifests[0]['instance'];
	}

	/**
	 * @return bool
	 */
	public function hasManifest() {
		return (bool) $this->manifests;
	}

	/**
	 * Pushes a php manifest instance onto the top of the stack.
	 *
	 * @param PhpManifest $manifest
	 * @param bool $exclusive
	 */
	public function pushManifest(PhpManifest $manifest, $exclusive = true) {
		array_unshift($this->manifests, array(
			'instance' => $manifest, 'exclusive' => $exclusive
		));
	}

	/**
	 * @return PhpManifest
	 */
	public function popManifest() {
		$manifest = array_shift($this->manifests);
		return $manifest['instance'];
	}

	/**
	 * @param string $class
	 */
	public function loadClass($class) {
		$class = strtolower($class);

		if(isset(self::$legacy_classes[$class])) {
			$old = $class;
			$new = self::$legacy_classes[$class];

			Deprecation::notice('3.1.0', "$old has been renamed to $new");
			class_alias($new, $old);
		} else {
			if($path = $this->getPath($class)) {
				require_once $path;
			}
		}
	}

	/**
	 * Returns the path for an item in the manifest, or in any previous
	 * manifests if later manifests aren't set to exclusive.
	 *
	 * @return string $name The fully qualified name to search for.
	 */
	public function getPath($name) {
		foreach($this->manifests as $manifest) {
			if($path = $manifest['instance']->getPath($name)) {
				return $path;
			}

			if($manifest['exclusive']) {
				return;
			}
		}
	}

	/**
	 * Returns true if a class, interface or trait exists in the manifest.
	 *
	 * @param string $class
	 * @return bool
	 */
	public function exists($name) {
		return class_exists($name, false) || $this->getPath($name);
	}

	/**
	 * Registers this class loader as an autoloader.
	 *
	 * @param bool $prepend
	 */
	public function register($prepend = false) {
		spl_autoload_register(array($this, 'loadClass'), true, $prepend);
	}

	/**
	 * Unregisters this class loader from the autoloader stack.
	 */
	public function unregister() {
		spl_autoload_unregister(array($this, 'loadClass'));
	}

}
