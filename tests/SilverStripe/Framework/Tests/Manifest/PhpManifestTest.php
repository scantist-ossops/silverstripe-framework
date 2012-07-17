<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Tests\Framework\Manifest;

use SilverStripe\Framework\Core\Module;
use SilverStripe\Framework\Manifest\Manifest;
use SilverStripe\Framework\Manifest\ModuleScanner;
use SilverStripe\Framework\Manifest\PhpManifest;
use SilverStripe\Framework\Testing\TestCase;

/**
 * Tests for the {@link PhpManifest} class.
 *
 * @package framework
 * @subpackage tests
 */
class PhpManifestTest extends TestCase {

	protected static $manifest;
	protected static $manifest_tests;
	protected static $path;

	public static function setUpBeforeClass() {
		self::$path = __DIR__ . '/fixtures/phpmanifest';

		$manifest = new Manifest(\PHPUnit_Framework_MockObject_Generator::getMock(
			'SilverStripe\\Framework\\Core\\Application'
		));
		$manifest->getCache()->setOptions(array(
			'readable' => false,
			'writable' => false
		));

		$noTestsPhp = new PhpManifest($manifest);
		$testsPhp   = new PhpManifest($manifest);

		$module   = new Module('phpmanifest', self::$path);
		$scanner  = new ModuleScanner();

		$scanner->setCallback($scanner::PHP, array($noTestsPhp, 'addFile'));
		$scanner->scan($module);

		$scanner->setIncludeTests(true);
		$scanner->setCallback($scanner::PHP, array($testsPhp, 'addFile'));
		$scanner->scan($module);

		$noTestsPhp->finalise();
		$testsPhp->finalise();

		self::$manifest = $noTestsPhp;
		self::$manifest_tests = $testsPhp;
	}

	public static function tearDownAfterClass() {
		self::$manifest = null;
		self::$manifest_tests = null;
	}

	public function testGetPath() {
		$expect = array(
			'ClassD' => 'classes/ClassD.php',
			'RootClass' => 'classes/RootClass.php',
			'Test\\ClassA' => 'classes/ClassA.php',
			'Test\\ClassB' => 'classes/ClassB.php',
			'Test\\ClassC' => 'classes/ClassC.php',
			'RootInterface' => 'interfaces/RootInterface.php',
			'Test\\NamespacedInterface' => 'interfaces/NamespacedInterface.php',
			'RootTrait' => 'traits/Traits.php',
			'Test\\NamespacedTrait' => 'traits/Traits.php'
		);

		$tests = array(
			'TestClass' => 'tests/TestClass.php',
			'TestInterface' => 'tests/TestInterface.php',
			'TestTrait' => 'tests/TestTrait.php'
		);

		foreach($expect as $name => $path) {
			$this->assertEquals(self::$path . '/' . $path, self::$manifest->getPath($name));
			$this->assertEquals(self::$path . '/' . $path, self::$manifest->getPath(strtolower($name)));
			$this->assertEquals(self::$path . '/' . $path, self::$manifest->getPath(strtoupper($name)));
		}

		foreach($expect + $tests as $name => $path) {
			$this->assertEquals(self::$path . '/' . $path, self::$manifest_tests->getPath($name));
			$this->assertEquals(self::$path . '/' . $path, self::$manifest_tests->getPath(strtolower($name)));
			$this->assertEquals(self::$path . '/' . $path, self::$manifest_tests->getPath(strtoupper($name)));
		}
	}

	public function testGetClasses() {
		$standard = array(
			'classd' => self::$path . '/classes/ClassD.php',
			'rootclass' => self::$path . '/classes/RootClass.php',
			'test\\classa' => self::$path . '/classes/ClassA.php',
			'test\\classb' => self::$path . '/classes/ClassB.php',
			'test\\classc' => self::$path . '/classes/ClassC.php',
		);

		$tests = $standard + array(
			'testclass' => self::$path . '/tests/TestClass.php'
		);

		$this->assertEquals($standard, self::$manifest->getClasses());
		$this->assertEquals($tests, self::$manifest_tests->getClasses());
	}

	public function testGetInterfaces() {
		$standard = array(
			'rootinterface' => self::$path . '/interfaces/RootInterface.php',
			'test\\namespacedinterface' => self::$path . '/interfaces/NamespacedInterface.php',
		);

		$tests = $standard + array(
			'testinterface' => self::$path . '/tests/TestInterface.php'
		);

		$this->assertEquals($standard, self::$manifest->getInterfaces());
		$this->assertEquals($tests, self::$manifest_tests->getInterfaces());
	}

	public function testGetTraits() {
		$standard = array(
			'roottrait' => self::$path . '/traits/Traits.php',
			'test\\namespacedtrait'  => self::$path . '/traits/Traits.php'
		);

		$tests = $standard + array(
			'testtrait' => self::$path . '/tests/TestTrait.php'
		);

		$this->assertEquals($standard, self::$manifest->getTraits());
		$this->assertEquals($tests, self::$manifest_tests->getTraits());
	}

	public function testGetDescendants() {
		$standard = array(
			'test\\classa' => array('test\\classc', 'classd'),
			'test\\classc' => array('classd')
		);

		$tests = $standard + array(
			'rootclass' => array('testclass')
		);

		$this->assertEquals($standard, self::$manifest->getDescendants());
		$this->assertEquals($tests, self::$manifest_tests->getDescendants());
	}

	public function testGetImplementors() {
		$standard = array(
			'rootinterface' => array('test\\classb'),
			'test\\namespacedinterface' => array('test\\classc')
		);

		$tests = array_merge_recursive($standard, array(
			'rootinterface' => array('testclass')
		));

		$this->assertEquals($standard, self::$manifest->getImplementors());
		$this->assertEquals($tests, self::$manifest_tests->getImplementors());
	}

}
