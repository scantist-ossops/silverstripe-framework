<?php
/**
 * @package framework
 * @subpackage manifest
 */

namespace SilverStripe\Framework\Manifest;

use SilverStripe\Framework\Core\ModuleInterface;

/**
 * An interface that a class can implement to be used a manifest. You can
 * use this to implement your own custom manifest classes.
 *
 * @package framework
 * @subpackage manifest
 */
interface ManifestInterface {

	/**
	 * Attempts to load the manifest from the cache, and returns TRUE if
	 * successful.
	 *
	 * @return bool
	 */
	public function load();

	/**
	 * Saves the manifest to the cache.
	 */
	public function save();

	/**
	 * Clears all information from the manifest so it can be rebuilt.
	 */
	public function clear();

	/**
	 * Finalises the manifest once building has been completed.
	 */
	public function finalise();

	/**
	 * Adds a file to the manifest.
	 *
	 * @param string $name
	 * @param string $path
	 * @param ModuleInterface $module
	 */
	public function addFile($name, $path, ModuleInterface $module);

}
