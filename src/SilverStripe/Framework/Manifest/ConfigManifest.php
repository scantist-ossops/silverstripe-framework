<?php
/**
 * @package framework
 * @subpackage manifest
 */

namespace SilverStripe\Framework\Manifest;

use SilverStripe\Framework\Core\Config;
use SilverStripe\Framework\Core\ModuleInterface;
use SilverStripe\Framework\Util\Dag;
use Symfony\Component\Yaml\Yaml;

/**
 * A utility class which builds a manifest of configuration items
 *
 * @package framework
 * @subpackage manifest
 */
class ConfigManifest implements ManifestInterface {

	const MAIN_CACHE_KEY = 'config-manifest-main';
	const FRAGMENTS_CACHE_KEY = 'config-manifest-fragments';
	const VARIANT_CACHE_KEY = 'config-variant-%s';

	protected $manifest;
	protected $hash;
	protected $regenerate;

	protected $variantSpec = array();
	protected $phpConfigs = array();
	protected $fragments = array();
	protected $config = array();

	public function __construct(Manifest $manifest) {
		$this->manifest = $manifest;
	}

	/**
	 * Returns the fully sorted, filtered and built config, either from the
	 * cache or by building it. The most recently requested variant is cached
	 * in memory.
	 *
	 * By default this derives the variant information from the environment,
	 * but one can also be passed in.
	 *
	 * @param array $env
	 * @return array
	 */
	public function getConfig($env = null) {
		$hash = $this->getVariantHash($env);

		if(!$this->config || $hash != $this->hash) {
			if($this->regenerate) {
				$config = false;
			} else {
				$config = $this->manifest->getCache()->getItem(sprintf(self::VARIANT_CACHE_KEY, $hash));
			}

			if($config) {
				$this->config = unserialize($config);
			} else {
				$this->buildConfig($env, $hash);
			}
		}

		return $this->config;
	}

	/**
	 * Returns an array of paths to PHP config files.
	 *
	 * @return array
	 */
	public function getPhpConfigs() {
		return $this->phpConfigs;
	}

	/**
	 * Returns the spec defining what information needs to collected to
	 * determine the correct YAML fragments to include.
	 *
	 * @return array
	 */
	public function getVariantSpec() {
		return $this->variantSpec;
	}

	/**
	 * Returns the hash that uniquely identifies the current config variant.
	 *
	 * The variant is the combination of classes, modules, environment variables
	 * and constants which select select which YAML fragments are included
	 * according to "only" and "except" rules.
	 *
	 * By default this reads values from the current environment, but you can
	 * get the specific hash for an environment by passing an environment array
	 * with environment, envvars and constants keys.
	 *
	 * @param array $env
	 * @return string
	 */
	public function getVariantHash($env = null) {
		$spec = $this->getVariantSpec();

		if(isset($spec['environment'])) {
			if($env) {
				$spec['environment'] = $env['type'];
			} else {
				$spec['environment'] = \Director::get_environment_type();
			}
		}

		if(isset($spec['envvars'])) foreach(array_keys($spec['envvars']) as $var) {
			if($env) {
				$spec['envvars'][$var] = isset($env['envvars'][$var]) ? $env['envvars'][$var] : null;
			} else {
				$spec['envvars'][$var] = isset($_ENV[$var]) ? $_ENV[$var] : null;
			}
		}

		if(isset($spec['constants'])) foreach($spec['constants'] as $const) {
			if($env) {
				$spec['constants'][$const] = isset($env['constants'][$const]) ? $env['constants'][$const] : null;
			} else {
				$spec['constants'][$const] = defined($const) ? constant($const) : null;
			}
		}

		return md5(serialize($spec));
	}

	public function load() {
		if($data = $this->manifest->getCache()->getItem(self::MAIN_CACHE_KEY)) {
			$data = unserialize($data);

			if(array_key_exists('phpconfigs', $data) && array_key_exists('variantspec', $data)) {
				$this->phpConfigs  = $data['phpconfigs'];
				$this->variantSpec = $data['variantspec'];
				return true;
			}
		}

		return false;
	}

	public function save() {
		$this->manifest->getCache()->setItem(self::MAIN_CACHE_KEY, serialize(array(
			'phpconfigs'  => $this->phpConfigs,
			'variantspec' => $this->variantSpec
		)));

		$this->manifest->getCache()->setItem(self::FRAGMENTS_CACHE_KEY, serialize(
			$this->fragments
		));
	}

	public function clear() {
		$this->config = array();
		$this->phpConfigs = array();
		$this->fragments = array();
		$this->variantSpec = array();
		$this->regenerate = true;
	}

	public function finalise() {
		$this->preFilterFragments();
		$this->sortFragments();
		$this->buildVariantSpec();
	}

	/**
	 * @access private
	 */
	public function addFile($name, $path, ModuleInterface $module) {
		if(substr($name, -3) == 'php') {
			$this->phpConfigs[] = $path;
		} else {
			$this->addYamlFile($name, $path, $module);
		}
	}

	/**
	 * Adds a YAML file to the manifest, either loading the parse result from
	 * the cache or re-parsing it.
	 */
	private function addYamlFile($name, $path, ModuleInterface $module) {
		$cache  = $this->manifest->getCache();
		$loaded = false;
		$key    = 'yml-' . md5($path) . '-' . md5_file($path);

		if($data = $cache->getItem($key)) {
			$data = unserialize($data);

			if(is_array($data) && count($data)) {
				$fragments = $data;
				$loaded    = true;
			}
		}

		if(!$loaded) {
			$fragments = $this->parseYamlFile($name, $path, $module);
			$cache->setItem($key, serialize($fragments));
		}

		$this->fragments = array_merge($this->fragments, $fragments);
	}

	/**
	 * Handles parsing a YAML file.
	 *
	 * Splits the file into header and fragment pairs, normalises some of the
	 * header values, assigns anonymous config names if none are assigned, and
	 * adds the fragments to the fragments collection.
	 */
	private function parseYamlFile($name, $path, ModuleInterface $module) {
		$fragments  = array();

		// Break the file into header and fragment chunks.
		$contents = file_get_contents($path);
		$contents = str_replace(array("\r\n", "\r"), "\n", $contents);
		$parts    = preg_split('/^---$/m', $contents, -1, PREG_SPLIT_NO_EMPTY);

		// The base header.
		$base = array(
			'module' => $module->getName(),
			'file'   => substr($name, 0, strrpos($name, '.'))
		);

		// If there's only one part, then add it as an anonymous fragment.
		if(count($parts) == 1) {
			$fragments[] = $base + array(
				'name'     => 'anonymous-1',
				'fragment' => Yaml::parse($parts[0])
			);
		} else {
			if(count($parts) % 2 != 0) {
				throw new \Exception(sprintf(
					'The config file "%s" contains an unequal number of headers and fragments',
					$path
				));
			}

			// Step through each header and fragment pair.
			for($i = 0; $i < count($parts); $i += 2) {
				$header = Yaml::parse($parts[$i]);
				$header = array_change_key_case($header, CASE_LOWER);

				if(!isset($header['name'])) {
					$header['name'] = 'anonymous-' . ($i / 2 + 1);
				}

				// Normalise the before and after definitions. Multiple comma
				// separated definitions are allowed.
				foreach(array('before', 'after') as $rel) if(isset($header[$rel])) {
					$split = preg_split('/\s*,\s*/', $header[$rel], PREG_SPLIT_NO_EMPTY);
					$rels  = array();

					foreach($split as $part) {
						preg_match('!(\*|\w+) (?:\/(\*|\w+) (?:\*|\#(\w+))? )? !x', $part, $match);

						$rels[] = array(
							'module' => $match[1],
							'file'   => isset($match[2]) ? $match[2] : '*',
							'name'   => isset($match[3]) ? $match[3] : '*'
						);
					}

					$header[$rel] = $rels;
				}

				$fragments[] = $base + $header + array(
					'fragment' => Yaml::parse($parts[$i + 1])
				);
			}
		}

		return $fragments;
	}

	/**
	 * Sorts the YAML fragments so that the "before" and "after" rules are met.
	 * Throws an error if there's a loop
	 *
	 * We can't use regular sorts here - we need a topological sort. Easiest
	 * way is with a DAG, so build up a DAG based on the before/after rules, then
	 * sort that.
	 */
	protected function sortFragments() {
		$frags = array_values($this->fragments);
		$dag = new Dag($frags);

		foreach ($frags as $i => $frag) {
			foreach ($frags as $j => $otherfrag) {
				if ($i == $j) continue;

				$order = $this->relativeOrder($frag, $otherfrag);

				if ($order == 'before') $dag->addedge($i, $j);
				elseif ($order == 'after') $dag->addedge($j, $i);
			}
		}

		$this->fragments = $dag->sort();
	}

	/**
	 * Return a string "after", "before" or "undefined" depending on whether the
	 * YAML fragment array element passed as $a should be positioned after,
	 * before, or either compared to the YAML fragment array element passed
	 * as $b
	 *
	 * @param  $a Array - a YAML config fragment as loaded by addYAMLConfigFile
	 * @param  $b Array - a YAML config fragment as loaded by addYAMLConfigFile
	 * @return string "after", "before" or "undefined"
	 */
	protected function relativeOrder($a, $b) {
		$matchesSomeRule = array();

		// Do the same thing for after and before
		foreach (array('after'=>'before', 'before'=>'after') as $rulename => $opposite) {
			$matchesSomeRule[$rulename] = false;

			// If no rule specified, we don't match it
			if (isset($a[$rulename])) {

				foreach ($a[$rulename] as $rule) {
					$matchesRule = true;

					foreach(array('module', 'file', 'name') as $part) {
						$partMatches = true;

						// If part is *, we match _unless_ the opposite rule has a non-* matcher than also matches $b
						if ($rule[$part] == '*') {
							if (isset($a[$opposite])) foreach($a[$opposite] as $oppositeRule) {
								if ($oppositeRule[$part] == $b[$part]) { $partMatches = false; break; }
							}
						}
						else {
							$partMatches = ($rule[$part] == $b[$part]);
						}

						$matchesRule = $matchesRule && $partMatches;
						if (!$matchesRule) break;
					}

					$matchesSomeRule[$rulename] = $matchesSomeRule[$rulename] || $matchesRule;
				}
			}
		}

		// Check if it matches both rules - problem if so
		if ($matchesSomeRule['before'] && $matchesSomeRule['after']) {
			user_error('Config fragment requires itself to be both before _and_ after another fragment', E_USER_ERROR);
		}

		return $matchesSomeRule['before'] ? 'before' : ($matchesSomeRule['after'] ? 'after' : 'undefined');
	}

	/**
	 * This function filters the loaded yaml fragments, removing any that can't
	 * ever have their "only" and "except" rules match.
	 *
	 * Some tests in "only" and "except" rules need to be checked per request,
	 * but some are manifest based - these are invariant over requests and only
	 * need checking on manifest rebuild. So we can prefilter these before saving
	 * the fragments.
	 */
	protected function preFilterFragments() {
		foreach($this->fragments as $i => $frag) {
			if(
				   isset($frag['only']) && $this->matchesPreFilterRules($frag['only']) === false
				|| isset($frag['except']) && $this->matchesPreFilterRules($frag['except']) === true
			) {
				unset($this->fragments[$i]);
			}
		}
	}

	/**
	 * Returns false if the prefilterable parts of the rule aren't met, and
	 * true if they are, or null if it cannot be determined.
	 *
	 * @param array $rules
	 * @return bool
	 */
	protected function matchesPreFilterRules($rules) {
		reset($rules);

		while($rules) {
			$rule = strtolower(key($rules));
			$val  = array_shift($rules);

			if($rule == 'classexists') {
				if(!$this->manifest->getApplication()->getClassLoader()->exists($val)) {
					return false;
				}
			} elseif($rule == 'moduleexists') {
				if(!$this->manifest->getApplication()->getModule($val)) {
					return false;
				}
			}
		}

		// If there are any rules left we cannot determine a match at this time,
		// so null is returned.
		if($rules) {
			return true;
		}
	}

	/**
	 * Builds the variant spec, which is the list of values needed to generate
	 * a hash uniquely identifying a config variant.
	 */
	protected function buildVariantSpec() {
		$this->variantSpec['envvars'] = array();
		$this->variantSpec['constants'] = array();

		foreach($this->fragments as $fragment) {
			if(isset($fragment['only'])) $this->addVariantSpecRules($fragment['only']);
			if(isset($fragment['except'])) $this->addVariantSpecRules($fragment['except']);
		}
	}

	/**
	 * Adds a set of rules to the variant spec.
	 */
	protected function addVariantSpecRules($rules) {
		foreach ($rules as $k => $v) {
			switch (strtolower($k)) {
				case 'classexists':
				case 'moduleexists':
					// Classes and modules are a special case - we can pre-filter
					// on config regenerate because we already know if the class
					// or module exists
					break;

				case 'environment':
					$this->variantSpec['environment'] = true;
					break;

				case 'envvarset':
					$this->variantSpec['envvars'][$k] = $k;
					break;

				case 'constantdefined':
					$this->variantSpec['constants'][$k] = $k;
					break;

				default:
					$this->variantSpec['envvars'][$k] = $this->variantSpec['constants'][$k] = $k;
			}
		}
	}

	/**
	 * Calculates which fragments are applicable in the variant, and merges them
	 * into the config property, then saves the compiled variant.
	 */
	protected function buildConfig($env = null, $hash = null) {
		if(!$hash) {
			$hash = $this->getVariantHash($env);
		}

		if(!$this->fragments) {
			$this->fragments = unserialize(
				$this->manifest->getCache()->getItem(self::FRAGMENTS_CACHE_KEY)
			);
		}

		$this->config = array();
		$this->hash = $hash;
		$this->regenerate = false;

		foreach($this->fragments as $fragment) {
			if(
				   isset($fragment['only']) && !$this->matchesRules($fragment['only'], $env)
				|| isset($fragment['except']) && $this->matchesRules($fragment['except'], $env)
			) {
				continue;
			}

			foreach($fragment['fragment'] as $k => $v) {
				Config::merge_high_into_low($this->config[$k], $v);
			}
		}

		$this->manifest->getCache()->setItem(
			sprintf(self::VARIANT_CACHE_KEY, $hash), serialize($this->config)
		);
	}

	/**
	 * Returns false if the non-prefilterable parts of the rule aren't met, and
	 * true if they are
	 */
	protected function matchesRules($rules, $env = null) {
		foreach($rules as $k => $v) {
			switch(strtolower($k)) {
				case 'classexists':
				case 'moduleexists':
					break;

				case 'environment':
					if($v != 'dev' && $v != 'test' && $v != 'live') {
						throw new \Exception(sprintf(
							'Unknown environment type "%s"', $v
						));
					}

					if($env) {
						if($v != $env['type']) return false;
					} else {
						if($v != \Director::get_environment_type()) return false;
					}
					break;

				case 'envvarset':
					if($env) {
						if(!isset($env['envvars'][$v])) return false;
					} else {
						if(!isset($_ENV[$v])) return false;
					}
					break;

				case 'constantdefined':
					if($env) {
						if(!isset($env['constants'][$v])) return false;
					} else {
						if(!defined($v)) return false;
					}
					break;

				default:
					if($env) {
						if(isset($env['envvars'][$k]) && $env['envvars'][$k] == $v) break;
						if(isset($env['constants'][$k]) && $env['constants'][$k] == $v) break;
					} else {
						if(isset($_ENV[$k]) && $_ENV[$k] == $v) break;
						if(defined($k) && constant($k) == $v) break;
					}
					return false;
			}
		}

		return true;
	}

}
