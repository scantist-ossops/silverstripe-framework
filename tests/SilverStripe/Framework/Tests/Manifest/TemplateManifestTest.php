<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Tests\Framework\Manifest;

use SilverStripe\Framework\Core\Module;
use SilverStripe\Framework\Core\Theme;
use SilverStripe\Framework\Manifest\Manifest;
use SilverStripe\Framework\Manifest\TemplateManifest;
use SilverStripe\Framework\Testing\TestCase;

/**
 * Tests for the {@link TemplateManifest} class.
 *
 * @package framework
 * @subpackage tests
 */
class TemplateManifestTest extends TestCase {

	protected static $manifest;

	public static function setUpBeforeClass() {
		$application = \PHPUnit_Framework_MockObject_Generator::getMock(
			'SilverStripe\\Framework\\Core\\Application'
		);

		$manifest = new TemplateManifest(new Manifest($application));
		$module   = new Module('module', null);
		$theme    = new Theme('theme', null);

		$moduleTemplates = array(
			'module/subfolder/templates/Subfolder.ss',
			'module/templates/Layout/CustomPage.ss',
			'module/templates/Layout/Page.ss',
			'module/templates/Page.ss',
			'module/Root.ss'
		);

		$themeTemplates = array(
			'theme/templates/Includes/Include.ss',
			'theme/templates/Layout/Page.ss',
			'theme/templates/Page.ss'
		);

		foreach($moduleTemplates as $path) {
			$manifest->addFile(basename($path), $path, $module);
		}

		foreach($themeTemplates as $path) {
			$manifest->addFile(basename($path), $path, $theme);
		}

		self::$manifest = $manifest;
	}

	public static function tearDownAfterClass() {
		self::$manifest = null;
	}

	public function testGetTemplates() {
		$this->assertEquals(self::$manifest->getTemplates(), array(
			'root' => array(
				'module' => 'module/Root.ss'
			),
			'page' => array(
			'Layout' => 'module/templates/Layout/Page.ss',
				'main' => 'module/templates/Page.ss',
				'themes' => array('theme' => array(
					'Layout' => 'theme/templates/Layout/Page.ss',
					'main' => 'theme/templates/Page.ss'
				))
			),
			'custompage' => array(
				'Layout' => 'module/templates/Layout/CustomPage.ss'
			),
			'subfolder' => array(
				'main' => 'module/subfolder/templates/Subfolder.ss'
			),
			'include' => array('themes' => array(
				'theme' => array(
					'Includes' => 'theme/templates/Includes/Include.ss'
				)
			))
		));
	}

	public function testGetTemplate() {
		$expect = array(
			'main' => 'module/templates/Page.ss',
			'Layout' => 'module/templates/Layout/Page.ss',
			'themes' => array('theme' => array(
				'main' => 'theme/templates/Page.ss',
				'Layout' => 'theme/templates/Layout/Page.ss'
			))
		);

		$this->assertEquals($expect, self::$manifest->getTemplate('Page'));
		$this->assertEquals($expect, self::$manifest->getTemplate('PAGE'));
	}

}
