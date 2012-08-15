<?php
/**
 * @package framework
 * @subpackage manifest
 */

namespace SilverStripe\Framework\Manifest;

/**
 * Loads classes, interfaces and traits from a stack of {@link PhpManifest}
 * instances.
 *
 * @package framework
 * @subpackage manifest
 */
class ClassLoader {

	protected $manifests = array();

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
		if($path = $this->getPath($class)) {
			require_once $path;
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
