<?php
/**
 * @package framework
 * @subpackage manifest
 */

namespace SilverStripe\Framework\Manifest;

use SilverStripe\Framework\Core\ModuleInterface;
use SilverStripe\Framework\Reflection\PhpParser;
use SilverStripe\Framework\Reflection\TokenStream;

/**
 * @package framework
 * @subpackage manifest
 */
class PhpManifest implements ManifestInterface {

	const CACHE_KEY = 'php-manifest';

	/**
	 * @var Manifest
	 */
	protected $manifest;

	protected $classes = array();
	protected $interfaces = array();
	protected $traits = array();

	protected $roots = array();
	protected $children = array();
	protected $descendants = array();
	protected $implementors = array();

	public function __construct(Manifest $manifest) {
		$this->manifest = $manifest;
	}

	/**
	 * @return array
	 */
	public function getClasses() {
		return $this->classes;
	}

	/**
	 * @return array
	 */
	public function getInterfaces() {
		return $this->interfaces;
	}

	/**
	 * @return array
	 */
	public function getTraits() {
		return $this->traits;
	}

	/**
	 * @return array
	 */
	public function getDescendants() {
		return $this->descendants;
	}

	/**
	 * @return array
	 */
	public function getImplementors() {
		return $this->implementors;
	}

	/**
	 * Returns the path to a class, interface or trait if it exists in this
	 * manifest.
	 *
	 * @param string $name The fully qualified name.
	 * @return string
	 */
	public function getPath($name) {
		$name = strtolower($name);

		if($name[0] == '\\') {
			$name = substr($name, 1);
		}

		if(array_key_exists($name, $this->classes)) {
			return $this->classes[$name];
		}

		if(array_key_exists($name, $this->interfaces)) {
			return $this->interfaces[$name];
		}

		if(array_key_exists($name, $this->traits)) {
			return $this->traits[$name];
		}
	}

	public function load() {
		if($cached = $this->manifest->getCache()->getItem(self::CACHE_KEY)) {
			$keys   = array('classes', 'interfaces', 'traits', 'descendants', 'implementors');
			$cached = unserialize($cached);

			if(array_keys($cached) == $keys) {
				foreach($keys as $key) {
					$this->$key = $cached[$key];
				}

				return true;
			}
		}

		return false;
	}

	public function save() {
		$this->manifest->getCache()->setItem(self::CACHE_KEY, serialize(array(
			'classes'      => $this->classes,
			'interfaces'   => $this->interfaces,
			'traits'       => $this->traits,
			'descendants'  => $this->descendants,
			'implementors' => $this->implementors
		)));
	}

	public function clear() {
		$properties = array(
			'classes', 'interfaces', 'traits', 'roots', 'children', 'descendants', 'implementors'
		);

		foreach($properties as $prop) {
			$this->$prop = array();
		}
	}

	public function finalise() {
		foreach($this->roots as $root) {
			$this->coalesceDescendants($root);
		}
	}

	public function addFile($name, $path, ModuleInterface $module) {
		$cache  = $this->manifest->getCache();
		$loaded = false;
		$key    = 'php' . '-' . md5($path) . '-' . md5_file($path);

		if($data = $cache->getItem($key)) {
			$data = unserialize($data);

			if(
				array_key_exists('classes', $data) &&
				array_key_exists('interfaces', $data) &&
				array_key_exists('traits', $data)
			) {
				$classes    = $data['classes'];
				$interfaces = $data['interfaces'];
				$traits     = $data['traits'];

				$loaded = true;
			}
		}

		if(!$loaded) {
			$stream = new TokenStream(file_get_contents($path));
			$parser = new PhpParser($stream);
			$parser->parse();

			$classes    = $parser->getClasses();
			$interfaces = $parser->getInterfaces();
			$traits     = $parser->getTraits();

			$data = serialize(array(
				'classes'    => $classes,
				'interfaces' => $interfaces,
				'traits'     => $traits
			));
			$cache->setItem(
				$key, $data, array('tags' => array('phpparse'))
			);
		}

		foreach($classes as $class) {
			$this->addClass($class, $path);
		}

		foreach($interfaces as $interface) {
			$this->addInterface($interface, $path);
		}

		foreach($traits as $trait) {
			$this->addTrait($trait, $path);
		}
	}

	private function addClass($info, $path) {
		$name = $info['name'];
		$lower = strtolower($name);
		$extends = strtolower($info['extends']);
		$implements = $info['implements'];

		if($duplicate = $this->getPath($name)) {
			throw new \Exception(sprintf(
				'Two files contain the same item "%s": "%s" and "%s"',
				$name,
				$duplicate,
				$path
			));
		}

		$this->classes[$lower] = $path;

		if($extends) {
			if(array_key_exists($extends, $this->children)) {
				$this->children[$extends][] = $name;
			} else {
				$this->children[$extends] = array($name);
			}
		} else {
			$this->roots[] = $lower;
		}

		if($implements) {
			foreach($implements as $interface) {
				$interface = strtolower($interface);

				if(!array_key_exists($interface, $this->implementors)) {
					$this->implementors[$interface] = array($name);
				} else {
					$this->implementors[$interface][] = $name;
				}
			}
		}
	}

	private function addInterface($info, $path) {
		$name = strtolower($info['name']);

		if($duplicate = $this->getPath($name)) {
			throw new \Exception(sprintf(
				'The item "%s" exists in two locations: "%s" and "%s"',
				$name,
				$duplicate,
				$path
			));
		}

		$this->interfaces[$name] = $path;
	}

	private function addTrait($name, $path) {
		$name = strtolower($name);

		if($duplicate = $this->getPath($name)) {
			throw new \Exception(sprintf(
				'The item "%s" exists in two locations: "%s" and "%s"',
				$name,
				$duplicate,
				$path
			));
		}

		$this->traits[$name] = $path;
	}

	private function coalesceDescendants($class) {
		$class = strtolower($class);

		if(array_key_exists($class, $this->children)) {
			$this->descendants[$class] = array();

			foreach($this->children[$class] as $child) {
				$this->descendants[$class] = array_merge(
					$this->descendants[$class],
					array($child),
					$this->coalesceDescendants($child)
				);
			}

			return $this->descendants[$class];
		} else {
			return array();
		}
	}

}
