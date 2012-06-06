<?php
/**
 * @package framework
 * @subpackage core
 */

namespace SilverStripe\Framework\Core;

/**
 * Default implementation of {@link ModuleInterface}, allows module information
 * to be set using constuctor arguments.
 *
 * @package framework
 * @subpackage core
 */
class Module implements ModuleInterface {

	protected $name;
	protected $path;

	/**
	 * @param string $name
	 * @param string $path
	 * @param int $priority
	 */
	public function __construct($name, $path) {
		$this->name = $name;
		$this->path = $path;
	}

	public function getName() {
		return $this->name;
	}

	public function getPath() {
		return $this->path;
	}

}
