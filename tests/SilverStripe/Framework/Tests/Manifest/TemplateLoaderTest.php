<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Framework\Tests\Manifest;

use SilverStripe\Framework\Manifest\TemplateLoader;
use SilverStripe\Framework\Testing\TestCase;

/**
 * Tests for the {@link ClassLoader} class.
 *
 * @package framework
 * @subpackage tests
 */
class TemplateLoaderTest extends TestCase {

	public function testManifestOperations() {
		$first = $this->getMock(
			'SilverStripe\\Framework\\Manifest\\TemplateManifest',
			array(),
			array(),
			'',
			false
		);
		$second = clone $first;

		$loader = new TemplateLoader($first);
		$this->assertTrue($loader->hasManifest());
		$this->assertSame($first, $loader->getManifest());

		$loader->pushManifest($second);
		$this->assertTrue($loader->hasManifest());
		$this->assertSame($second, $loader->getManifest());

		$this->assertSame($second, $loader->popManifest());
		$this->assertSame($first, $loader->popManifest());

		$this->assertFalse($loader->hasManifest());
		$this->assertNull($loader->getManifest());
	}

	public function testGetPaths() {
		$manifest = $this->getMock(
			'SilverStripe\\Framework\\Manifest\\TemplateManifest',
			array('getTemplate'),
			array(),
			'',
			false
		);

		$templates = array(
			'Page' => array(
				'main' => '/module/templates/Page.ss',
				'Layout' => '/module/templates/Layout/Page.ss',
				'themes' => array(
					'theme' => array(
						'main' => '/themes/theme/templates/Page.ss',
						'Layout' => '/themes/theme/templates/Layout/Page.ss'
					)
				)
			),
			'CustomPage' => array(
				'Layout' => '/module/templates/Layout/CustomPage.ss'
			)
		);

		$callback = function($template) use ($templates) {
			if(isset($templates[$template])) return $templates[$template];
		};

		$manifest->expects($this->any())
		         ->method('getTemplate')
		         ->will($this->returnCallback($callback));

		$loader = new TemplateLoader($manifest);

		$expectPage = array(
			'main'   => '/module/templates/Page.ss',
			'Layout' => '/module/templates/Layout/Page.ss'
		);
		$expectPageThemed = array(
			'main'   => '/themes/theme/templates/Page.ss',
			'Layout' => '/themes/theme/templates/Layout/Page.ss'
		);

		$this->assertEquals($expectPage, $loader->getPaths('Page'));
		$this->assertEquals($expectPage, $loader->getPaths(array('Foo', 'Page')));
		$this->assertEquals($expectPageThemed, $loader->getPaths('Page', 'theme'));

		$expectPageLayout = array('main' => '/module/templates/Layout/Page.ss');
		$expectPageLayoutThemed = array('main' => '/themes/theme/templates/Layout/Page.ss');

		$this->assertEquals($expectPageLayout, $loader->getPaths('Layout/Page'));
		$this->assertEquals($expectPageLayoutThemed, $loader->getPaths('Layout/Page', 'theme'));

		$expectCustomPage = array(
			'main'   => '/module/templates/Page.ss',
			'Layout' => '/module/templates/Layout/CustomPage.ss'
		);
		$this->assertEquals($expectCustomPage, $loader->getPaths(array('CustomPage', 'Page')));
	}

}
