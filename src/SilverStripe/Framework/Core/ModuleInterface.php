<?php
/**
 * @package framework
 * @subpackage core
 */

namespace SilverStripe\Framework\Core;

/**
 * An interface that can be implemented to allow an object to be registered as
 * a module in an application.
 *
 * @package framework
 * @subpackage core
 */
interface ModuleInterface {

	/**
	 * Returns the name of the module, usually a lowercase alpha string.
	 *
	 * @return string
	 */
	public function getName();

	/**
	 * Returns the absolute path to the module.
	 *
	 * @return string
	 */
	public function getPath();

}
