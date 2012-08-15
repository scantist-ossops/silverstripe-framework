<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Framework\Tests\Manifest;

use SilverStripe\Framework\Manifest\ClassLoader;
use SilverStripe\Framework\Testing\TestCase;

/**
 * Tests for the {@link ClassLoader} class.
 *
 * @package framework
 * @subpackage tests
 */
class ClassLoaderTest extends TestCase {

	protected $first;
	protected $second;

	public function setUp() {
		$mock = $this->getMock(
			'SilverStripe\\Framework\\Manifest\\PhpManifest',
			array('getPath'),
			array(),
			'',
			false
		);

		$this->first  = $mock;
		$this->second = clone $mock;

		$this->first
			->expects($this->any())
			->method('getPath')
			->will($this->returnCallback(function($name) {
				if($name == 'ClassLoaderTest\First') return 'first.php';
			}));

		$this->second
			->expects($this->any())
			->method('getPath')
			->will($this->returnCallback(function($name) {
				if($name == 'ClassLoaderTest\Second') return 'second.php';
			}));

		parent::setUp();
	}

	public function testManifestOperation() {
		$loader = new ClassLoader($this->first);
		$this->assertTrue($loader->hasManifest());
		$this->assertSame($this->first, $loader->getManifest());

		$loader->pushManifest($this->second);
		$this->assertTrue($loader->hasManifest());
		$this->assertSame($this->second, $loader->getManifest());

		$this->assertSame($this->second, $loader->popManifest());
		$this->assertSame($this->first, $loader->popManifest());

		$this->assertFalse($loader->hasManifest());
		$this->assertNull($loader->getManifest());
	}

	public function testExists() {
		$loader = new ClassLoader($this->first);
		$this->assertTrue($loader->exists(__CLASS__));
		$this->assertTrue($loader->exists('ClassLoaderTest\First'));
		$this->assertFalse($loader->exists('ClassLoaderTest\Second'));
	}

	public function testExclusive() {
		$loader = new ClassLoader($this->first);
		$this->assertNotNull($loader->getPath('ClassLoaderTest\First'));
		$this->assertNull($loader->getPath('ClassLoaderTest\Second'));

		$loader->pushManifest($this->second, false);
		$this->assertNotNull($loader->getPath('ClassLoaderTest\First'));
		$this->assertNotNull($loader->getPath('ClassLoaderTest\Second'));

		$loader->popManifest();
		$loader->pushManifest($this->second, true);
		$this->assertNull($loader->getPath('ClassLoaderTest\First'));
		$this->assertNotNull($loader->getPath('ClassLoaderTest\Second'));
	}

	public function testGetPath() {
		$loader = new ClassLoader();
		$loader->pushManifest($this->first, false);
		$loader->pushManifest($this->second, false);

		$this->assertEquals('first.php', $loader->getPath('ClassLoaderTest\First'));
		$this->assertEquals('second.php', $loader->getPath('ClassLoaderTest\Second'));
	}

}
