<?php
/**
 * @package framework
 * @subpackage filesystem
 */

namespace SilverStripe\Framework\Filesystem;

use SilverStripe\Framework\Core\Application;
use SilverStripe\Framework\Core\ApplicationInterface;
use SilverStripe\Framework\Core\Theme;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Copies assets from individual modules into an assets directory.
 *
 * @package framework
 * @subpackage filesystem
 */
class AssetsInstaller {

	protected $application;

	/**
	 * Installs assets to the specified target directory. Each module will have
	 * its assets mirrored to a directory with the same name as the module name
	 * in the public directory.
	 *
	 * @param string $target
	 * @param bool $symlink
	 */
	public function install($target = null) {
		$app = $this->getApplication();
		$fs = new Filesystem();

		if(!$target) {
			$target = $app->getPublicPath();
		}

		// If the application lives in the web root, then the assets don't need
		// to be copied.
		if($app->getBasePath() == $target) {
			return;
		}

		if(!is_dir($target)) {
			throw new \InvalidArgumentException(sprintf(
				'The target directory "%s" does not exist', $target
			));
		}

		foreach($app->getModules() as $module) {
			if($module instanceof Theme) {
				$moduleTarget = $target . '/themes';
			} else {
				$moduleTarget = $target;
			}

			$fs->mkdir($moduleTarget);

			foreach($module->getAssetDirs() as $dir) {
				$path = $module->getPath() . '/' . $dir;
				$to = $moduleTarget . '/' . strtolower($module->getName()) . '/' . $dir;

				if(!is_dir($path)) {
					continue;
				}

				$fs->remove($to);
				$fs->mirror($path, $to, null, array('copy_on_windows' =>  true));
			}
		}
	}

	/**
	 * @return ApplicationInterface
	 */
	public function getApplication() {
		return $this->application ?: Application::curr();
	}

	public function setApplication(ApplicationInterface $app) {
		$this->application = $app;
	}

}
