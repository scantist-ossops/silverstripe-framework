<?php
/**
 * @package framework
 */

namespace SilverStripe\Framework;

use SilverStripe\Framework\Core\Application;

/**
 * A legacy-style application where modules and themes are installed in the
 * webroot and automatically discovered. This is normally invoked by `main.php`.
 *
 * @package framework
 */
class WebrootApplication extends Application {

	public function getBasePath() {
		return dirname(getcwd());
	}

	public function getPublicPath() {
		return $this->getBasePath();
	}

	protected function registerModules() {
		parent::registerModules();

		$this->getModules()->addFromDirectory($this->getBasePath());
		$this->getModules()->addThemesFromDirectory($this->getBasePath() . '/themes');
	}

}
