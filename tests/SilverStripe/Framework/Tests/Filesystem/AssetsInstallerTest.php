<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Framework\Tests\Filesystem;

use SilverStripe\Framework\Core\Module;
use SilverStripe\Framework\Filesystem\AssetsInstaller;
use SilverStripe\Framework\Testing\TestApplication;
use SilverStripe\Framework\Testing\TestCase;

/**
 * Tests for the {@link AssetsInstaller} class.
 *
 * @package framework
 * @subpackage tests
 */
class AssetsInstallerTest extends TestCase {

	public function testInstallation() {
		$app = new TestApplication();
		$app->getModules()->add(new Module('module', __DIR__ . '/fixtures/assetsinstaller'));

		$path = $app->getTempPath() . '/assetsinstallertest';

		\Filesystem::makeFolder($path);

		$installer = new AssetsInstaller();
		$installer->setApplication($app);
		$installer->install($path);

		$this->assertFileExists("$path/module/css/test.css");
		$this->assertFileExists("$path/module/javascript/test.js");

		\Filesystem::removeFolder($path);
	}

}
