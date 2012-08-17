<?php
/**
 * @package framework
 * @subpackage core
 */

namespace SilverStripe\Framework\Core;

/**
 * A theme is a type of module which can contain only template files, and has
 * some additional logic in the template manifest builder.
 *
 * @package framework
 * @subpackage core
 */
class Theme implements ModuleInterface {

	protected $name;
	protected $path;

	/**
	 * @param string $name
	 * @param string $path
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

	public function getAssetDirs() {
		return array('css', 'images', 'javascript');
	}

}
