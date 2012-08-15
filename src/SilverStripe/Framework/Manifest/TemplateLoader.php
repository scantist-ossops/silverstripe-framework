<?php
/**
 * @package framework
 * @subpackage manifest
 */

namespace SilverStripe\Framework\Manifest;

use SilverStripe\Framework\Core\Application;
use SilverStripe\Framework\Dev\Deprecation;

/**
 * Loads templates from a stack of {@link TemplateManifest} instances.
 *
 * @package framework
 * @subpackage manifest
 */
class TemplateLoader {

	protected $manifests = array();

	/**
	 * @deprecated 3.1 Use `Application::curr()->get('TemplateLoader')`
	 */
	public static function instance() {
		Deprecation::notice('3.1');
		return Application::curr()->get('TemplateLoader');
	}

	public function __construct(TemplateManifest $manifest = null) {
		if($manifest) $this->pushManifest($manifest);
	}

	/**
	 * Returns the currently active template manifest instance.
	 *
	 * @return TemplateManifest
	 */
	public function getManifest() {
		if($this->manifests) return $this->manifests[0];
	}

	/**
	 * @return bool
	 */
	public function hasManifest() {
		return (bool) $this->manifests;
	}

	/**
	 * @param TemplateManifest $manifest
	 */
	public function pushManifest(TemplateManifest $manifest) {
		array_unshift($this->manifests, $manifest);
	}

	/**
	 * @return TemplateManifest
	 */
	public function popManifest() {
		return array_shift($this->manifests);
	}

	/**
	 * Attempts to find possible candidate templates from a set of template
	 * names and a theme.
	 *
	 * The template names can be passed in as plain strings, or be in the
	 * format "type/name", where type is the type of template to search for
	 * (e.g. Includes, Layout).
	 *
	 * @param string|array $templates
	 * @param string $theme
	 * @return array
	 */
	public function getPaths($templates, $theme = null) {
		$result = array();

		foreach((array) $templates as $template) {
			$found = false;

			if(strpos($template, '/')) {
				list($type, $template) = explode('/', $template, 2);
			} else {
				$type = null;
			}

			if($candidates = $this->getManifest()->getTemplate($template)) {
				if ($theme && isset($candidates['themes'][$theme])) {
					$found = $candidates['themes'][$theme];
				} else {
					unset($candidates['themes']);
					$found = $candidates;
				}

				if ($found) {
					if ($type && isset($found[$type])) {
						$found = array('main' => $found[$type]);
					}

					$result = array_merge($found, $result);
				}
			}
		}

		return $result;
	}

}
