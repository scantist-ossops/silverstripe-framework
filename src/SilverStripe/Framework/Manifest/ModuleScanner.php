<?php
/**
 * @package framework
 * @subpackage manifest
 */

namespace SilverStripe\Framework\Manifest;

use SilverStripe\Framework\Core\ModuleInterface;
use SilverStripe\Framework\Filesystem\FileFinder;

/**
 * Scans a module's directory for all php, config and template files, calling
 * callbacks when they are encountered.
 *
 * @package framework
 * @subpackage manifest
 */
class ModuleScanner {

	const ALL         = -1;
	const PHP         = 1;
	const YAML_CONFIG = 2;
	const PHP_CONFIG  = 4;
	const TEMPLATE    = 8;

	/**
	 * @var int
	 */
	protected $scanFor = self::ALL;

	/**
	 * @var bool
	 */
	protected $includeTests = false;

	/**
	 * @var array
	 */
	protected $callbacks = array();

	/**
	 * Returns the callback handler for a file type.
	 *
	 * @param int $type
	 * @return callback
	 */
	public function getCallback($type) {
		if(isset($this->callbacks[$type])) return $this->callbacks[$type];
	}

	/**
	 * Sets the callback handler to be called when a file type is encountered.
	 *
	 * @param int $type
	 * @param callback $callback
	 * @return callback
	 */
	public function setCallback($type, $callback) {
		$this->callbacks[$type] = $callback;
	}

	/**
	 * Sets several callbacks at the same time.
	 *
	 * @param array $callbacks
	 */
	public function setCallbacks(array $callbacks) {
		foreach($callbacks as $type => $callback) {
			$this->setCallback($type, $callback);
		}
	}

	/**
	 * Returns the bitmask controlling which items to scan for.
	 *
	 * @return int
	 */
	public function getScanFor() {
		return $this->scanFor;
	}

	/**
	 * Sets the bitmask controlling which items to scan for.
	 *
	 * @param int $for
	 */
	public function setScanFor($for) {
		$this->scanFor = $for;
	}

	/**
	 * @return bool
	 */
	public function getIncludeTests() {
		return $this->includeTests;
	}

	/**
	 * Controls whether or not to include tests.
	 *
	 * @param bool $include
	 */
	public function setIncludeTests($include) {
		$this->includeTests = $include;
	}

	/**
	 * Scans a module and calls the configured callbacks.
	 *
	 * @param ModuleInterface $module
	 */
	public function scan(ModuleInterface $module) {
		$this->module = $module;

		if(!is_dir($module->getPath())) {
			throw new \Exception(sprintf(
				'Module path "%s" for module "%s" does not exist.',
				$module->getPath(),
				$module->getName()
			));
		}

		$finder = new FileFinder();
		$finder->setOptions(array(
			'accept_dir_callback' => array($this, 'acceptDir'),
			'file_callback'       => array($this, 'handleFile')
		));
		$finder->find($module->getPath());
	}

	/**
	 * @access private
	 */
	public function acceptDir($name, $path, $depth) {
		if($depth == 1 && $name == 'lang') {
			return false;
		}

		if(!$this->getIncludeTests() && $depth == 1 && $name == 'tests') {
			return false;
		}

		if(file_exists("$path/_manifest_exclude")) {
			return false;
		}
	}

	/**
	 * @access private
	 */
	public function handleFile($name, $path, $depth) {
		$ext = substr(strrchr($name, '.'), 1);

		switch($ext) {
			case 'php':
				$this->handlePhpFile($name, $path);
				break;

			case 'yml':
			case 'yaml':
				$this->handleYamlFile($name, $path);
				break;

			case 'ss':
				$this->handleTemplateFile($name, $path);
				break;
		}
	}

	protected function callback() {
		$args = func_get_args();
		$type = array_shift($args);

		if($callback = $this->getCallback($type)) {
			call_user_func_array($callback, $args);
		}
	}

	private function handlePhpFile($name, $path) {
		if($name[0] == '_') {
			if($name == '_config.php' && $this->getScanFor() & self::PHP_CONFIG) {
				$this->callback(self::PHP_CONFIG, $name, $path, $this->module);
			}
		} else {
			if($this->getScanFor() & self::PHP) {
				$this->callback(self::PHP, $name, $path, $this->module);
			}
		}
	}

	private function handleYamlFile($name, $path) {
		if(basename(dirname($path)) == '_config' && $this->getScanFor() & self::YAML_CONFIG) {
			$this->callback(self::YAML_CONFIG, $name, $path, $this->module);
		}
	}

	private function handleTemplateFile($name, $path) {
		if($this->getScanFor() & self::TEMPLATE) {
			$this->callback(self::TEMPLATE, $name, $path, $this->module);
		}
	}

}
