<?php
/**
 * @package framework
 * @subpackage core
 */

namespace SilverStripe\Framework\Core;

/**
 * Contains configuration settings for a class.
 *
 * @package framework
 * @subpackage core
 */
class ConfigForClass {

	protected $class;

	/**
	 * @param string $class
	 */
	public function __construct($class) {
		$this->class = $class;
	}

	public function __get($name) {
		return $this->get($name);
	}

	public function __set($name, $val) {
		return Application::curr()->getConfig()->update($this->class, $name, $val);
	}

	public function get($name, $options = 0) {
		return Application::curr()->getConfig()->get($this->class, $name, $options);
	}

	/**
	 * @return ConfigForClass
	 */
	public function forClass($class) {
		return Application::curr()->getConfig()->forClass($class);
	}

}
