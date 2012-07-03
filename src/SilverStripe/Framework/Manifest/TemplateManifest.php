<?php
/**
 * @package framework
 * @subpackage manifest
 */

namespace SilverStripe\Framework\Manifest;

use SilverStripe\Framework\Core\ModuleInterface;
use SilverStripe\Framework\Core\Theme;

/**
 * Contains all the template paths in the application, broken down by name, type
 * and theme.
 *
 * @package framework
 * @subpackage manifest
 */
class TemplateManifest implements ManifestInterface {

	const CACHE_KEY = 'template-manifest';

	/**
	 * @var Manifest
	 */
	protected $manifest;

	/**
	 * @var array
	 */
	protected $templates = array();

	public function __construct(Manifest $manifest) {
		$this->manifest = $manifest;
	}

	/**
	 * @return array
	 */
	public function getTemplates() {
		return $this->templates;
	}

	/**
	 * Returns an array of candidates for a template name.
	 *
	 * @param string $name
	 * @return array
	 */
	public function getTemplate($name) {
		$name = strtolower($name);

		if (array_key_exists($name, $this->templates)) {
			return $this->templates[$name];
		} else {
			return array();
		}
	}

	public function clear() {
		$this->templates = array();
	}

	public function finalise() {
		// Nothing needs to be done.
	}

	public function load() {
		if($data = $this->manifest->getCache()->getItem(self::CACHE_KEY)) {
			$this->templates = unserialize($data);
			return true;
		} else {
			return false;
		}
	}

	public function save() {
		$this->manifest->getCache()->setItem(self::CACHE_KEY, serialize($this->templates));
	}

	public function addFile($name, $path, ModuleInterface $module) {
		if($module instanceof Theme) {
			$theme = strtok($module->getName(), '_');
		} else {
			$theme = null;
		}

		$type = basename(dirname($path));
		$name = strtolower(substr($name, 0, -3));

		if($type == 'templates') {
			$type = 'main';
		}

		if($theme) {
			$this->templates[$name]['themes'][$theme][$type] = $path;
		} else {
			$this->templates[$name][$type] = $path;
		}
	}

}
