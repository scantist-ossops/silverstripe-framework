<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Framework\Tests\Manifest;

use SilverStripe\Framework\Core\Module;
use SilverStripe\Framework\Manifest\ConfigManifest;
use SilverStripe\Framework\Manifest\Manifest;
use SilverStripe\Framework\Testing\TestCase;

/**
 * Tests for the {@link ConfigManifest} class.
 *
 * @package framework
 * @subpackage tests
 */
class ConfigManifestTest extends TestCase {

	public function testOperation() {
		$moduleA = new Module('modulea', __DIR__ . '/fixtures/configmanifest/modulea');
		$moduleB = new Module('moduleb', __DIR__ . '/fixtures/configmanifest/moduleb');
		$moduleC = new Module('modulec', __DIR__ . '/fixtures/configmanifest/modulec');
		$modules = array('modulea' => $moduleA, 'moduleb' => $moduleB, 'modulec' => $moduleC);

		$application = $this->getMock('SilverStripe\\Framework\\Core\\Application');

		$application->expects($this->any())
			->method('getModules')
			->will($this->returnValue($modules));

		$application->expects($this->any())
			->method('getModule')
			->will($this->returnCallback(function($name) use($modules) {
				if(isset($modules[$name])) return $modules[$name];
			}));

		$manifest = new Manifest($application);
		$manifest->getCache()->setOptions(array('readable' => false, 'writable' => false));
		$manifest->init();

		$tests = new Manifest($application, true);
		$tests->getCache()->setOptions(array('readable' => false, 'writable' => false));
		$tests->init();

		$default = $manifest->getConfigManifest();
		$tests   = $tests->getConfigManifest();

		$this->assertEquals($default->getPhpConfigs(), array(
			__DIR__ . '/fixtures/configmanifest/modulea/_config.php',
			__DIR__ . '/fixtures/configmanifest/moduleb/subfolder/_config.php'
		));

		$first = $default->getConfig(array(
			'type'      => 'dev',
			'constants' => array('CONSTANT' => true),
			'envvars'   => array('ENV' => 'test')
		));
		$second = $default->getConfig(array(
			'type'      => 'live',
			'constants' => array(),
			'envvars'   => array()
		));

		$this->assertEquals(array('modulea', 'moduleb', 'modulec'), $first['Config']['priority']);

		$this->assertContains('devonly', $first['Config']['array']);
		$this->assertNotContains('devonly', $second['Config']['array']);

		$this->assertContains('constonly', $first['Config']['array']);
		$this->assertNotContains('constonly', $second['Config']['array']);

		$this->assertNotContains('envexcept', $first['Config']['array']);
		$this->assertContains('envexcept', $second['Config']['array']);

		$this->assertContains('modulebonly', $first['Config']['array']);
		$this->assertNotContains('moduledonly', $first['Config']['array']);
	}

	public function testMatchesRules() {
		$manifest = $this->getMock(
			'SilverStripe\\Framework\\Manifest\\ConfigManifest',
			array(),
			array(),
			'',
			false
		);

		$env = array(
			'type' => 'test',
			'envvars' => array('ENVVAR' => 'envvalue'),
			'constants' => array('CONSTANT' => 'constvalue')
		);

		$this->assertTrue($this->call($manifest, 'matchesRules', array(
			array()
		)));

		$this->assertTrue($this->call($manifest, 'matchesRules', array(array(
			'moduleexists' => 'anything',
			'classexists'  => 'anything'
		))));

		$this->assertTrue($this->call($manifest, 'matchesRules', array(
			array('environment' => 'test'), $env
		)));

		$this->assertFalse($this->call($manifest, 'matchesRules', array(
			array('environment' => 'dev'), $env
		)));

		$this->setExpectedException('\Exception');
		$this->call($manifest, 'matchesRules', array(
			array('environment' => 'unknown'), $env)
		);

		$this->assertTrue($this->call($manifest, 'matchesRules', array(
			array('envvarset' => 'ENVVAR'), $env
		)));
		$this->assertFalse($this->call($manifest, 'matchesRules', array(
			array('envvarset' => 'CONSTANT'), $env
		)));

		$this->assertTrue($this->call($manifest, 'matchesRules', array(
			array('constantdefined' => 'CONSTANT'), $env
		)));
		$this->assertFalse($this->call($manifest, 'matchesRules', array(
			array('constantdefined' => 'ENVVAR'), $env
		)));

		$this->assertTrue($this->call($manifest, 'matchesRules', array(
			array('CONSTANT' => 'constvalue'), $env
		)));
		$this->assertTrue($this->call($manifest, 'matchesRules', array(
			array('ENVVAR' => 'envvalue'), $env
		)));
		$this->assertFalse($this->call($manifest, 'matchesRules', array(
			array('CONSTANT' => 'envvalue'), $env
		)));
		$this->assertFalse($this->call($manifest, 'matchesRules', array(
			array('ENVVAR' => 'constvalue'), $env
		)));
	}

	protected function call($inst, $method, $args = array()) {
		$reflector = new \ReflectionObject($inst);

		$method = $reflector->getMethod('matchesRules');
		$method->setAccessible(true);

		return $method->invokeArgs($inst, $args);
	}

}
