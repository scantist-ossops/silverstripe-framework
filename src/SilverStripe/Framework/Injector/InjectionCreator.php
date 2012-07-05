<?php
/**
 * @package framework
 * @subpackage injector
 */

namespace SilverStripe\Framework\Injector;

/**
 * The default injection creator.
 *
 * @package framework
 * @subpackage injector
 */
class InjectionCreator implements InjectionCreatorInterface {

	public function create(Injector $injector, $class, $params = array()) {
		$reflector = new \ReflectionClass($class);

		if($params) {
			return $reflector->newInstanceArgs($injector->convertServiceProperty($params));
		} else {
			return $reflector->newInstance();
		}
	}

}
