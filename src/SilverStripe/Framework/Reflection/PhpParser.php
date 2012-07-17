<?php
/**
 * @package framework
 * @subpackage reflection
 */

namespace SilverStripe\Framework\Reflection;

/**
 * Parses a token stream and extracts the classes, interfaces and traits.
 *
 * @package framework
 * @subpackage reflection
 */
class PhpParser {

	protected $stream;
	protected $namespace;

	protected $aliases = array();
	protected $classes = array();
	protected $interfaces = array();
	protected $traits = array();

	public function __construct(TokenStream $stream) {
		$this->stream = $stream;
	}

	/**
	 * @return array
	 */
	public function getAliases() {
		return $this->aliases;
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
	 * Parses the token stream, must be called before data is retrieved.
	 */
	public function parse() {
		$T_TRAIT = defined('T_TRAIT') ? T_TRAIT : -1;

		while(!$this->stream->finished()) {
			switch($this->stream->getToken()) {
				case T_NAMESPACE:
					$this->parseNamespace();
					break;

				case T_USE:
					$this->parseUse();
					break;

				case T_CLASS:
					$this->parseClass();
					break;

				case T_INTERFACE:
					$this->parseInterface();
					break;

				case $T_TRAIT:
					$this->parseTrait();
					break;

				default:
					$this->stream->next();
					break;
			}
		}
	}

	/**
	 * Converts an unqualified, partially qualified or fully qualified name into
	 * a fully qualified name.
	 *
	 * @param string $name
	 * @return string
	 */
	public function resolve($name) {
		if($name[0] == '\\') {
			return substr($name, 1);
		}

		if(($pos = strpos($name, '\\')) !== false) {
			$namespace = substr($name, 0, $pos);

			if(isset($this->aliases[$namespace])) {
				return $this->aliases[$namespace] . '\\' . substr($name, $pos + 1);
			}
		} else {
			if(isset($this->aliases[$name])) {
				return $this->aliases[$name];
			}
		}

		if($this->namespace) {
			return $this->namespace . '\\' . $name;
		} else {
			return $name;
		}
	}

	private function parseNamespace() {
		if(!$this->stream->is(T_NAMESPACE)) {
			throw new \Exception('Expected a T_NAMESPACE token.');
		}

		$this->stream->next();

		$name = $this->parseName();
		$name = ltrim($name, '\\');

		$this->namespace = $name;
	}

	private function parseUse() {
		if(!$this->stream->is(T_USE)) {
			throw new \Exception('Expected a T_USE token.');
		}

		$this->stream->next();

		// Check we're not in a lambda use definition.
		if($this->stream->is('(')) {
			return;
		}

		while(true) {
			$name = $this->parseName();
			$name = ltrim($name, '\\');

			if($this->stream->is(T_AS)) {
				$this->stream->next();
				$as = $this->stream->getValue();
				$this->stream->next();
			} else {
				if(($pos = strrpos($name, '\\')) !== false) {
					$as = substr($name, $pos + 1);
				} else {
					$as = $name;
				}
			}

			if(array_key_exists($as, $this->aliases)) {
				throw new \Exception("The namespace alias '$as' already exists.");
			}

			$this->aliases[$as] = $name;

			if(!$this->stream->is(',')) {
				break;
			}

			$this->stream->next();
		}
	}

	private function parseClass() {
		$extends    = '';
		$implements = array();

		// Class name
		if(!$this->stream->is(T_CLASS)) {
			throw new \Exception('An unexpected token was encountered (expected T_CLASS).');
		}

		$this->stream->next();

		if($this->namespace) {
			$name = $this->namespace . '\\' . $this->stream->getValue();
		} else {
			$name = $this->stream->getValue();
		}

		$this->stream->next();

		// Parent class
		if($this->stream->is(T_EXTENDS)) {
			$this->stream->next();

			$extends = $this->parseName();
			$extends = $this->resolve($extends);
		}

		// Implemented interfaces
		if($this->stream->is(T_IMPLEMENTS)) {
			$implements = $this->parseNameList();
		}

		$this->classes[] = array(
			'name' => $name,
			'extends' => $extends,
			'implements' => $implements
		);
	}

	private function parseInterface() {
		$extends = array();

		if(!$this->stream->is(T_INTERFACE)) {
			throw new \Exception('An unexpected token was encountered (expected T_INTERFACE).');
		}

		$this->stream->next();

		if(!$this->stream->is(T_STRING)) {
			throw new \Exception('An unexpected token was encountered (expected T_STRING).');
		}

		if($this->namespace) {
			$name = $this->namespace . '\\' . $this->stream->getValue();
		} else {
			$name = $this->stream->getValue();
		}

		$this->stream->next();

		if($this->stream->is(T_EXTENDS)) {
			$extends = $this->parseNameList();
		}

		$this->interfaces[] = array(
			'name' => $name,
			'extends' => $extends
		);
	}

	private function parseTrait() {
		if(!$this->stream->is('T_TRAIT')) {
			throw new \Exception('An unexpected token was encountered (expected T_TRAIT).');
		}

		$this->stream->next();

		if(!$this->stream->is(T_STRING)) {
			throw new \Exception('An unexpected token was encountered (expected T_STRING).');
		}

		if($this->namespace) {
			$name = $this->namespace . '\\' . $this->stream->getValue();
		} else {
			$name = $this->stream->getValue();
		}

		$this->stream->next();
		$this->traits[] = $name;
	}

	private function parseNameList() {
		$names = array();

		while(true) {
			$this->stream->next();

			$name = $this->parseName();
			$names[] = $this->resolve($name);

			if(!$this->stream->is(',')) {
				break;
			}
		}

		return $names;
	}

	private function parseName() {
		$name = '';

		while($this->stream->is(T_STRING) || $this->stream->is(T_NS_SEPARATOR)) {
			$name .= $this->stream->getValue();
			$this->stream->next();
		}

		return $name;
	}

}
