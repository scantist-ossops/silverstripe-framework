<?php
/**
 * @package framework
 * @subpackage core
 */

namespace SilverStripe\Framework\Core;

/**
 * An interface that is implemented by the application object.
 *
 * The application object is the main object responsible for managing the
 * environment and responding to requests. Each application instance has a
 * manifest, config object and class loader. It also has methods to handle
 * requests.
 *
 * @see Application
 * @package framework
 * @subpackage core
 */
interface ApplicationInterface {

	/**
	 * Initialises the application so it is ready to begin handling requests.
	 */
	public function start();

	/**
	 * Stops the application, should be called after the response has been
	 * generated.
	 */
	public function stop();

	/**
	 * Generates a response for the current request and returns it.
	 *
	 * @return Response
	 */
	public function handle();

	/**
	 * Gets a dependency by name.
	 *
	 * @see \SilverStripe\Framework\Injector\Injector::get()
	 * @param string $name
	 * @return object
	 */
	public function get($name);

	/**
	 * Returns the registered module instances.
	 *
	 * @return \SilverStripe\Framework\Core\ModuleInterface[]
	 */
	public function getModules();

	/**
	 * Returns a module instance by name.
	 *
	 * @param string $name
	 * @return \SilverStripe\Framework\Core\ModuleInterface
	 */
	public function getModule($name);

	/**
	 * @return \SilverStripe\Framework\Manifest\Manifest
	 */
	public function getManifest();

	/**
	 * @return \SilverStripe\Framework\Core\Config
	 */
	public function getConfig();

	/**
	 * @return \SilverStripe\Framework\Injector\Injector
	 */
	public function getInjector();

	/**
	 * @return \SilverStripe\Framework\Manifest\ClassLoader
	 */
	public function getClassLoader();

	/**
	 * Returns the path to the base of the application.
	 *
	 * @return string
	 */
	public function getBasePath();

	/**
	 * Returns the path to the public accessible web root.
	 *
	 * @return string
	 */
	public function getPublicPath();

	/**
	 * @return string
	 */
	public function getTempPath();

}
