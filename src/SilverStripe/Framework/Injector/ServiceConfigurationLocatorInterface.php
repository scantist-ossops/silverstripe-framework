<?php
/**
 * @package framework
 * @subpackage injector
 */

namespace SilverStripe\Framework\Injector;

/**
 * Implemented by classes used to locate configuration for a particular named
 * service.
 *
 * @package framework
 * @subpackage injector
 */
interface ServiceConfigurationLocatorInterface {

	/**
	 * Returns the configuration for a service, or null if it can't be found.
	 *
	 * @param string $name
	 * @return array
	 */
	public function locateConfigFor($name);

}
