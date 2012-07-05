<?php
/**
 * @package framework
 * @subpackage injector
 */

namespace SilverStripe\Framework\Injector;

/**
 * Implemented by classes used by the injector for creating new objects.
 *
 * @package framework
 * @subpackage injector
 */
interface InjectionCreatorInterface {

	/**
	 * Creates and returns a new class instance.
	 *
	 * @param Injector $injector
	 * @param string $class
	 * @param array $params
	 */
	public function create(Injector $injector, $class, $params = array());

}
