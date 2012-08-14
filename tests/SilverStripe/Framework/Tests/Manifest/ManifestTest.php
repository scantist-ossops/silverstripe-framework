<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Framework\Tests\Manifest;

use SilverStripe\Framework\Core\Module;
use SilverStripe\Framework\Manifest\Manifest;
use SilverStripe\Framework\Testing\TestCase;

/**
 * Tests for the {@link Manifest} class.
 *
 * @package framework
 * @subpackage tests
 */
class ManifestTest extends TestCase {

	public function testOperation() {
		$self     = $this;
		$module   = new Module('manifest', __DIR__ . '/fixtures/manifest');
		$app      = $this->getMock('SilverStripe\\Framework\\Core\\Application');
		$manifest = new Manifest($app);

		$php = $this->getMock('SilverStripe\\Framework\\Manifest\\PhpManifest', array(), array($manifest));
		$php->expects($this->once())
			->method('addFile')
			->will($this->returnCallback(function($name, $path) use($self) {
				$self->assertEquals(__DIR__ . '/fixtures/manifest/Php.php', $path);
			}));

		$conf = $this->getMock('SilverStripe\\Framework\\Manifest\\ConfigManifest', array(), array($manifest));
		$conf->expects($this->once())
			->method('addFile')
			->will($this->returnCallback(function($name, $path) use($self) {
				$self->assertEquals(__DIR__ . '/fixtures/manifest/_config.php', $path);
			}));

		$tmpl = $this->getMock('SilverStripe\\Framework\\Manifest\\TemplateManifest', array(), array($manifest));
		$tmpl->expects($this->once())
			->method('addFile')
			->will($this->returnCallback(function($name, $path) use($self) {
				$self->assertEquals(__DIR__ . '/fixtures/manifest/Template.ss', $path);
			}));

		$app->expects($this->any())
			->method('getModules')
			->will($this->returnValue(array($module)));

		$app->expects($this->any())
			->method('getModule')
			->will($this->returnCallback(function($name) use($module) {
				if($module == 'manifest') return $module;
			}));

		$manifest->getCache()->setOptions(array('readable' => false, 'writable' => false));
		$manifest->setPhpManifest($php);
		$manifest->setConfigManifest($conf);
		$manifest->setTemplateManifest($tmpl);
		$manifest->init();
	}

}
