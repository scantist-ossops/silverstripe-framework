<?php
/**
 * @package framework
 * @subpackage manifest
 */

namespace SilverStripe\Framework\Manifest;

use SilverStripe\Framework\Core\Application;
use Zend\Cache\StorageFactory;
use Zend\Cache\Storage\Adapter\AdapterInterface;

/**
 * The central manifest object, which contains a PHP, config and template
 * manifest. This object acts as a manager to build, cache and provide access
 * to these manifests.
 *
 * @package framework
 * @subpackage manifest
 */
class Manifest {

	protected $application;
	protected $cache;
	protected $includeTests;

	protected $phpManifest;
	protected $configManifest;
	protected $templateManifest;

	/**
	 * Constructs the manifest, including setting whether or not tests are
	 * included.
	 *
	 * @param Application $application
	 * @param bool $includeTests
	 */
	public function __construct(Application $application, $includeTests = false) {
		$this->application  = $application;
		$this->includeTests = (bool) $includeTests;
	}

	/**
	 * Initialises the manifests, loading them from the cache and rebuilding
	 * if neccesary.
	 *
	 * @param bool $forceBuild Forces the manifest to be rebuilt, default false.
	 */
	public function init($forceBuild = false) {
		$loaded = false;

		// Attempt to load the manifest from the cache. If any one manifest
		// can't be loaded, rebuild the whole thing.
		if(!$forceBuild) {
			$loaded = (
				   $this->getPhpManifest()->load()
				&& $this->getConfigManifest()->load()
				&& $this->getTemplateManifest()->load()
			);
		}

		if(!$loaded) {
			$this->scan();
			$this->getPhpManifest()->save();
			$this->getConfigManifest()->save();
			$this->getTemplateManifest()->save();
		}
	}

	/**
	 * @return Application
	 */
	public function getApplication() {
		return $this->application;
	}

	/**
	 * @return bool
	 */
	public function getIncludeTests() {
		return $this->includeTests;
	}

	/**
	 * @return AdapterInterface
	 */
	public function getCache() {
		if(!$this->cache) {
			$this->cache = StorageFactory::factory(array(
				'adapter' => array(
					'name' => 'filesystem',
					'options' => array('cache_dir' => $this->getApplication()->getTempPath())
				),
				'options' => array(
					'namespace' => $this->getIncludeTests() ? 'manifest-tests' : 'manifest'
				)
			));
		}

		return $this->cache;
	}

	/**
	 * @param AdapterInterface $cache
	 */
	public function setCache(AdapterInterface $cache) {
		$this->cache = $cache;
	}

	/**
	 * @return PhpManifest
	 */
	public function getPhpManifest() {
		if(!$this->phpManifest) {
			$this->phpManifest = new PhpManifest($this);
		}

		return $this->phpManifest;
	}

	/**
	 * @param ManifestInterface $manifest
	 */
	public function setPhpManifest(ManifestInterface $manifest) {
		$this->phpManifest = $manifest;
	}

	/**
	 * @return ConfigManifest
	 */
	public function getConfigManifest() {
		if(!$this->configManifest) {
			$this->configManifest = new ConfigManifest($this);
		}

		return $this->configManifest;
	}

	/**
	 * @param ManifestInterface $manifest
	 */
	public function setConfigManifest(ManifestInterface $manifest) {
		$this->configManifest = $manifest;
	}

	/**
	 * @return TemplateManifest
	 */
	public function getTemplateManifest() {
		if(!$this->templateManifest) {
			$this->templateManifest = new TemplateManifest($this);
		}

		return $this->templateManifest;
	}

	/**
	 * @param ManifestInterface $manifest
	 */
	public function setTemplateManifest(ManifestInterface $manifest) {
		$this->templateManifest = $manifest;
	}

	/**
	 * Builds the manifests using a module scanner for each application module.
	 */
	private function scan() {
		$php  = $this->getPhpManifest();
		$conf = $this->getConfigManifest();
		$tmpl = $this->getTemplateManifest();

		$scanner = new ModuleScanner();
		$scanner->setIncludeTests($this->getIncludeTests());
		$scanner->setCallbacks(array(
			$scanner::PHP         => array($php, 'addFile'),
			$scanner::YAML_CONFIG => array($conf, 'addFile'),
			$scanner::PHP_CONFIG  => array($conf, 'addFile'),
			$scanner::TEMPLATE    => array($tmpl, 'addFile')
		));

		$php->clear();
		$conf->clear();
		$tmpl->clear();

		foreach($this->application->getModules() as $module) {
			if($module instanceof Theme) {
				$scanner->setScanFor($scanner::TEMPLATE);
			} else {
				$scanner->setScanFor($scanner::ALL);
			}

			$scanner->scan($module);
		}

		$php->finalise();
		$conf->finalise();
		$tmpl->finalise();
	}

}
