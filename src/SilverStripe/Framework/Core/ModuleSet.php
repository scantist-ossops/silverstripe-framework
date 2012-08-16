<?php
/**
 * @package framework
 * @subpackage core
 */

namespace SilverStripe\Framework\Core;

use Symfony\Component\Yaml\Yaml;
use Zend\Cache\StorageFactory;

/**
 * Contains a set of modules that are running as part of an application, as
 * well as various ways to register them.
 *
 * @package framework
 * @subpackage core
 * @todo Add caching
 */
class ModuleSet implements \ArrayAccess, \IteratorAggregate {

	const TYPE_MODULE = 'module';
	const TYPE_THEME = 'theme';
	const TYPE_WIDGET = 'widget';

	protected $application;
	protected $flush;
	protected $cache;
	protected $modules = array();

	public function __construct(ApplicationInterface $application, $flush = false) {
		$this->application = $application;
		$this->flush = $flush;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function exists($name) {
		return isset($this->modules[$name]);
	}

	/**
	 * @param string $name
	 * @return ModuleInterface|null
	 */
	public function get($name) {
		if($this->exists($name)) return $this->modules[$name];
	}

	public function add(ModuleInterface $module) {
		$name = $module->getName();

		if($this->exists($name)) {
			throw new \Exception("The module $name is already registered");
		}

		$this->modules[$name] = $module;
	}

	public function addFromDetails($name, $path, $type = self::TYPE_MODULE) {
		if($type != self::TYPE_MODULE && $type != self::TYPE_THEME && $type != self::TYPE_WIDGET) {
			throw new \InvalidArgumentException(sprintf(
				'Invalid module type "%s"', $type
			));
		}

		if($type == self::TYPE_THEME) {
			$module = new Theme($name, $this->getPath($path));
		} else {
			$module = new Module($name, $this->getPath($path));
		}

		$this->add($module);
	}

	/**
	 * Registers modules from a yaml file. The yaml file must have a "modules"
	 * key defined.
	 *
	 * Each elements can either have a "class" key pointing to the module
	 * class to use, or a "name" and "path" (and optionally "type") key.
	 *
	 * @param string $path
	 */
	public function addFromYaml($path) {
		$path = $this->getPath($path);
		$info = Yaml::parse($path);
		$found = array();

		$cache = $this->getCache();
		$cached = false;
		$cacheKey = 'directory-' . md5($path);

		if(!is_array($info['modules'])) {
			throw new \Exception('No modules key was set in the modules yaml file');
		}

		foreach($info['modules'] as $details) {
			if(isset($details['class'])) {
				if(!class_exists($details['class'])) {
					throw new \Exception(sprintf(
						'The module class "%s" does not exist', $details['class']
					));
				}

				$this->add(new $details['class']());
			} else {
				if(!isset($details['name']) || !isset($details['path'])) {
					throw new \Exception(
						'Invalid module details were provided. Each module must ' .
						'either have a "class" key, or a "name" and "path" key'
					);
				}

				$this->addFromDetails(
					$details['name'],
					$details['path'],
					isset($details['type']) ? $details['type'] : self::TYPE_MODULE
				);
			}
		}
	}

	/**
	 * Scans a directory for any modules directly inside it, adding them to
	 * the application.
	 *
	 * A module is denoted by the presence of a "_config.php" file or a
	 * "_config" directory.
	 *
	 * @param string $path
	 */
	public function addFromDirectory($path) {
		if(!$this->flush) {
		}

		$path = $this->getPath($path);
		$iterator = new \DirectoryIterator($path);

		foreach($iterator as $item) {
			if(!$item->isDir()) {
				continue;
			}

			if($item->getBasename() == 'framework') {
				continue;
			}

			$path = $item->getPathname();

			if(file_exists("$path/_config.php") || is_dir("$path/_config")) {
				$this->addFromDetails($item->getBasename(), $path);
			}
		}
	}

	public function addThemesFromDirectory($path) {
		$path = $this->getPath($path);
		$iterator = new \DirectoryIterator($path);

		foreach($iterator as $item) {
			if($item->isDir()) {
				$this->addFromDetails($item->getBasename(), $path, self::TYPE_THEME);
			}
		}
	}

	/**
	 * @return ApplicationInterface
	 */
	public function getApplication() {
		return $this->application;
	}

	/**
	 * @return array
	 */
	public function toArray() {
		return $this->modules;
	}

	/**
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		return isset($this->modules[$offset]);
	}

	/**
	 * @param string $offset
	 * @return ModuleInterface|null
	 */
	public function offsetGet($offset) {
		if(isset($this->modules[$offset])) return $this->modules[$offset];
	}

	/**
	 * @ignore
	 */
	public function offsetSet($offset, $value) {
		throw new \Exception('You cannot call offsetSet on a module set');
	}

	/**
	 * @ignore
	 */
	public function offsetUnset($offset) {
		throw new \Exception('You cannot call offsetUnset on a module set');
	}

	/**
	 * @return ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator($this->modules);
	}

	/**
	 * @return AdapterInterface
	 */
	protected function getCache() {
		if(!$this->cache) {
			$this->cache = StorageFactory::factory(array(
				'adapter' => array(
					'name' => 'filesystem',
					'options' => array('cache_dir' => $this->getApplication()->getTempPath())
				),
				'options' => array(
					'namespace' => 'modules'
				)
			));
		}

		return $this->cache;
	}

	protected function getPath($path) {
		if($path[0] == '/' || preg_match('~^[a-zA-Z]:[\\\\/]~', $path)) {
			return $path;
		} else {
			return $this->getApplication()->getBasePath() . '/' . $path;
		}
	}

}
