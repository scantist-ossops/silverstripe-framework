<?php
/**
 * @package framework
 * @subpackage tasks
 */

use SilverStripe\Framework\Core\Application;
use SilverStripe\Framework\Filesystem\AssetsInstaller;

/**
 * A task that runs the asset installer.
 *
 * @package framework
 * @subpackage tasks
 */
class InstallAssetsTask extends BuildTask {

	protected $title = 'Install Assets';

	protected $description = 'Installs modules assets to the webroot';

	public function run($request) {
		$application = Application::curr();
		$installer = new AssetsInstaller();
		$path = $application->getPublicPath();

		$installer->setApplication($application);
		$installer->install($path);

		printf("Installed assets to '%s'", $path);
	}

}
