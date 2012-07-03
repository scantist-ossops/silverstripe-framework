<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Tests\Framework\Manifest;

use SilverStripe\Framework\Core\Module;
use SilverStripe\Framework\Manifest\ModuleScanner;
use SilverStripe\Framework\Testing\TestCase;

/**
 * Tests for the {@link ModuleScanner} class.
 *
 * @package framework
 * @subpackage tests
 */
class ModuleScannerTest extends TestCase {

	protected $scanner;
	protected $module;

	public function setUp() {
		$this->scanner = new ModuleScanner();
		$this->module  = new Module('modulescanner', __DIR__ . '/fixtures/modulescanner');

		parent::setUp();
	}

	public function testScan() {
		$this->assertScanMatches($this->scanner, $this->module, array(
			'php' => array('php/Php.php'),
			'yamlconfig' => array('_config/config.yml'),
			'phpconfig' => array('_config.php'),
			'template' => array('templates/Template.ss')
		));

		$scanner = clone $this->scanner;
		$scanner->setIncludeTests(true);

		$this->assertScanMatches($scanner, $this->module, array(
			'php' => array('php/Php.php', 'tests/Test.php'),
			'yamlconfig' => array('_config/config.yml', 'tests/_config/test.yml'),
			'phpconfig' => array('_config.php', 'tests/_config.php'),
			'template' => array('templates/Template.ss', 'tests/Test.ss')
		));

		$scanner = clone $this->scanner;
		$scanner->setScanFor($scanner::PHP);
		$this->assertScanMatches($scanner, $this->module, array(
			'php' => array('php/Php.php')
		));

		$scanner->setScanFor($scanner::ALL & ~$scanner::TEMPLATE);
		$this->assertScanMatches($scanner, $this->module, array(
			'php' => array('php/Php.php'),
			'yamlconfig' => array('_config/config.yml'),
			'phpconfig' => array('_config.php')
		));
	}

	/**
	 * @expectedException \Exception
	 */
	public function testNonExistantPathThrowsException() {
		$path    = __DIR__ . '/fixtures/modulescanner';
		$scanner = new ModuleScanner();

		$scanner->scan(new Module('nonexistant', "$path/nonexistant"));
	}

	protected function assertScanMatches($scanner, $module, $expects) {
		$found  = array_fill_keys(array('php', 'yamlconfig', 'phpconfig', 'template'), array());
		$length = strlen($module->getPath()) + 1;

		$scanner->setCallbacks(array(
			$scanner::PHP => function($name, $path) use(&$found, $length) {
				$found['php'][] = substr($path, $length);
			},
			$scanner::YAML_CONFIG => function($name, $path) use(&$found, $length) {
				$found['yamlconfig'][] = substr($path, $length);
			},
			$scanner::PHP_CONFIG => function($name, $path) use(&$found, $length) {
				$found['phpconfig'][] = substr($path, $length);
			},
			$scanner::TEMPLATE => function($name, $path) use(&$found, $length) {
				$found['template'][] = substr($path, $length);
			}
		));

		$scanner->scan($module);

		foreach($found as $type => $paths) {
			if(isset($expects[$type])) {
				sort($expects[$type]);
				sort($paths);

				$this->assertEquals($expects[$type], $paths);
			} else {
				$this->assertEquals(array(), $paths);
			}
		}
	}

}
