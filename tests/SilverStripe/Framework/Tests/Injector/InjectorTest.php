<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Framework\Tests\Injector;

use SilverStripe\Framework\Core\Application;
use SilverStripe\Framework\Core\Config;
use SilverStripe\Framework\Injector\ApplicationServiceConfigurationLocator;
use SilverStripe\Framework\Injector\InjectionCreator;
use SilverStripe\Framework\Injector\Injector;
use SilverStripe\Framework\Testing\TestCase;

/**
 * Tests for the dependency injector
 * 
 * Note that these are SS conversions of the existing Simpletest unit tests
 *
 * @author marcus@silverstripe.com.au
 * @license BSD License http://silverstripe.org/bsd-license/
 * @package framework
 * @subpackage tests
 */
class InjectorTest extends TestCase {

	private $injector;

	public function setUp() {
		parent::setUp();

		$this->injector = new Injector();
		$this->injector->registerNamedService('Config', Application::curr()->getConfig());
		$this->injector->setConfigLocator(new ApplicationServiceConfigurationLocator(Application::curr()));
	}

	public function testBasicInjector() {
		$injector = $this->injector;
		$injector->setAutoScanProperties(true);
		$config = array(array('src' => __DIR__ . '/Services/SampleService.php',));

		$injector->load($config);
		$this->assertEquals($injector->hasService('SampleService'), 'SampleService');

		$myObject = new TestObject();
		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->sampleService), 'SampleService');
	}

	public function testConfiguredInjector() {
		$injector = $this->injector;
		$services = array(
			array(
				'src' => __DIR__ . '/Services/AnotherService.php',
				'properties' => array('config_property' => 'Value'),
			),
			array(
				'src' => __DIR__ . '/Services/SampleService.php',
			)
		);

		$injector->load($services);
		$this->assertTrue($injector->hasService('SampleService') == 'SampleService');
		// We expect a false because the 'AnotherService' is actually
		// just a replacement of the SampleService
		$this->assertTrue($injector->hasService('AnotherService') == 'AnotherService');

		$item = $injector->get('AnotherService');

		$this->assertEquals('Value', $item->config_property);
	}

	public function testIdToNameMap() {
		$injector = $this->injector;
		$services = array(
			'FirstId' => __NAMESPACE__ . '\\TestObject',
			'SecondId' => __NAMESPACE__ . '\\OtherTestObject',
		);

		$injector->load($services);

		$this->assertTrue($injector->hasService('FirstId') == 'FirstId');
		$this->assertTrue($injector->hasService('SecondId') == 'SecondId');

		$this->assertTrue($injector->get('FirstId') instanceof TestObject);
		$this->assertTrue($injector->get('SecondId') instanceof OtherTestObject);
	}

	public function testReplaceService() {
		$injector = $this->injector;
		$injector->setAutoScanProperties(true);

		$config = array(array('src' => __DIR__ . '/Services/SampleService.php'));

		// load
		$injector->load($config);

		// inject
		$myObject = new TestObject();
		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->sampleService), 'SampleService');

		// also tests that ID can be the key in the array
		$config = array('SampleService' => array('src' => __DIR__ . '/Services/AnotherService.php')); // , 'id' => 'SampleService'));
		// load
		$injector->load($config);

		$injector->inject($myObject);
		$this->assertEquals('AnotherService', get_class($myObject->sampleService));
	}
	
	public function testUpdateSpec() {
		$injector = $this->injector;
		$services = array(
			'AnotherService' => array(
				'src' => __DIR__ . '/Services/AnotherService.php',
				'properties' => array(
					'filters' => array(
						'One',
						'Two',
					)
				),
			)
		);

		$injector->load($services);
		
		$injector->updateSpec('AnotherService', 'filters', 'Three');
		$another = $injector->get('AnotherService');
		
		$this->assertEquals(3, count($another->filters));
		$this->assertEquals('Three', $another->filters[2]);
	}

	public function testAutoSetInjector() {
		$injector = $this->injector;
		$injector->setAutoScanProperties(true);
		$injector->addAutoProperty('auto', 'somevalue');
		$config = array(array('src' => __DIR__ . '/Services/SampleService.php',));
		$injector->load($config);

		$this->assertTrue($injector->hasService('SampleService') == 'SampleService');
		// We expect a false because the 'AnotherService' is actually
		// just a replacement of the SampleService

		$myObject = new TestObject();

		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->sampleService), 'SampleService');
		$this->assertEquals($myObject->auto, 'somevalue');
	}

	public function testSettingSpecificProperty() {
		$injector = $this->injector;
		$config = array(__NAMESPACE__ . '\\OtherTestObject');
		$injector->load($config);
		$injector->setInjectMapping(__NAMESPACE__ . '\\TestObject', 'sampleService', __NAMESPACE__ . '\\OtherTestObject');
		$testObject = $injector->get(__NAMESPACE__ . '\\TestObject');

		$this->assertEquals(get_class($testObject->sampleService), __NAMESPACE__ . '\\OtherTestObject');
	}

	public function testSettingSpecificMethod() {
		$injector = $this->injector;
		$config = array('AnotherService');
		$injector->load($config);
		$injector->setInjectMapping(__NAMESPACE__ . '\\TestObject', 'setSomething', 'AnotherService', 'method');

		$testObject = $injector->get(__NAMESPACE__ . '\\TestObject');

		$this->assertEquals(get_class($testObject->sampleService), 'AnotherService');
	}
	
	public function testInjectingScopedService() {
		$injector = $this->injector;
		
		$config = array(
			'AnotherService',
			'AnotherService.DottedChild'	=> 'SampleService',
		);
		
		$injector->load($config);
		
		$service = $injector->get('AnotherService.DottedChild');
		$this->assertEquals(get_class($service), 'SampleService');
		
		$service = $injector->get('AnotherService.Subset');
		$this->assertEquals(get_class($service), 'AnotherService');
		
		$injector->setInjectMapping(__NAMESPACE__ . '\\TestObject', 'sampleService', 'AnotherService.Geronimo');
		$testObject = $injector->create(__NAMESPACE__ . '\\TestObject');
		$this->assertEquals(get_class($testObject->sampleService), 'AnotherService');
		
		$injector->setInjectMapping(__NAMESPACE__ . '\\TestObject', 'sampleService', 'AnotherService.DottedChild.AnotherDown');
		$testObject = $injector->create(__NAMESPACE__ . '\\TestObject');
		$this->assertEquals(get_class($testObject->sampleService), 'SampleService');
		
	}

	public function testInjectUsingConstructor() {
		$injector = $this->injector;
		$config = array(array(
				'src' => __DIR__ . '/Services/SampleService.php',
				'constructor' => array(
					'val1',
					'val2',
				)
				));

		$injector->load($config);
		$sample = $injector->get('SampleService');
		$this->assertEquals($sample->constructorVarOne, 'val1');
		$this->assertEquals($sample->constructorVarTwo, 'val2');

		$injector = $this->injector;
		$config = array(
			'AnotherService',
			array(
				'src' => __DIR__ . '/Services/SampleService.php',
				'constructor' => array(
					'val1',
					'%$AnotherService',
				)
			)
		);

		$injector->load($config);
		$sample = $injector->get('SampleService');
		$this->assertEquals($sample->constructorVarOne, 'val1');
		$this->assertEquals(get_class($sample->constructorVarTwo), 'AnotherService');
		
		$injector = $this->injector;
		$config = array(array(
				'src' => __DIR__ . '/Services/SampleService.php',
				'constructor' => array(
					'val1',
					'val2',
				)
				));

		$injector->load($config);
		$sample = $injector->get('SampleService');
		$this->assertEquals($sample->constructorVarOne, 'val1');
		$this->assertEquals($sample->constructorVarTwo, 'val2');
		
		// test constructors on prototype
		$injector = $this->injector;
		$config = array(array(
			'type'	=> 'prototype',
			'src' => __DIR__ . '/Services/SampleService.php',
			'constructor' => array(
				'val1',
				'val2',
			)
		));

		$injector->load($config);
		$sample = $injector->get('SampleService');
		$this->assertEquals($sample->constructorVarOne, 'val1');
		$this->assertEquals($sample->constructorVarTwo, 'val2');
		
		$again = $injector->get('SampleService');
		$this->assertFalse($sample === $again);
		
		$this->assertEquals($sample->constructorVarOne, 'val1');
		$this->assertEquals($sample->constructorVarTwo, 'val2');
	}

	public function testInjectUsingSetter() {
		$injector = $this->injector;
		$injector->setAutoScanProperties(true);
		$config = array(array('src' => __DIR__ . '/Services/SampleService.php',));

		$injector->load($config);
		$this->assertTrue($injector->hasService('SampleService') == 'SampleService');

		$myObject = new OtherTestObject();
		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->s()), 'SampleService');

		// and again because it goes down a different code path when setting things
		// based on the inject map
		$myObject = new OtherTestObject();
		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->s()), 'SampleService');
	}

	// make sure we can just get any arbitrary object - it should be created for us
	public function testInstantiateAnObjectViaGet() {
		$injector = $this->injector;
		$injector->setAutoScanProperties(true);
		$config = array(array('src' => __DIR__ . '/Services/SampleService.php',));

		$injector->load($config);
		$this->assertTrue($injector->hasService('SampleService') == 'SampleService');

		$myObject = $injector->get(__NAMESPACE__ . '\\OtherTestObject');
		$this->assertEquals(get_class($myObject->s()), 'SampleService');

		// and again because it goes down a different code path when setting things
		// based on the inject map
		$myObject = $injector->get(__NAMESPACE__ . '\\OtherTestObject');
		$this->assertEquals(get_class($myObject->s()), 'SampleService');
	}

	public function testCircularReference() {
		$services = array(
			'CircularOne' => __NAMESPACE__ . '\\CircularOne',
			'CircularTwo' => __NAMESPACE__ . '\\CircularTwo'
		);

		$injector = $this->injector;
		$injector->load($services);
		$injector->setAutoScanProperties(true);

		$obj = $injector->get(__NAMESPACE__ . '\\NeedsBothCirculars');

		$this->assertTrue($obj->circularOne instanceof CircularOne);
		$this->assertTrue($obj->circularTwo instanceof CircularTwo);
	}

	public function testPrototypeObjects() {
		$services = array(
			'CircularOne' => __NAMESPACE__ . '\\CircularOne',
			'CircularTwo' => __NAMESPACE__ . '\\CircularTwo',
			array('class' => __NAMESPACE__ . '\\NeedsBothCirculars', 'type' => 'prototype')
		);

		$injector = $this->injector;
		$injector->load($services);
		$injector->setAutoScanProperties(true);
		$obj1 = $injector->get(__NAMESPACE__ . '\\NeedsBothCirculars');
		$obj2 = $injector->get(__NAMESPACE__ . '\\NeedsBothCirculars');

		// if this was the same object, then $obj1->var would now be two
		$obj1->var = 'one';
		$obj2->var = 'two';

		$this->assertTrue($obj1->circularOne instanceof CircularOne);
		$this->assertTrue($obj1->circularTwo instanceof CircularTwo);

		$this->assertEquals($obj1->circularOne, $obj2->circularOne);
		$this->assertNotEquals($obj1, $obj2);
	}

	public function testSimpleInstantiation() {
		$services = array(__NAMESPACE__ . '\\CircularOne', __NAMESPACE__ . '\\CircularTwo');

		$injector = $this->injector;
		$injector->load($services);

		// similar to the above, but explicitly instantiating this object here
		$obj1 = $injector->create(__NAMESPACE__ . '\\NeedsBothCirculars');
		$obj2 = $injector->create(__NAMESPACE__ . '\\NeedsBothCirculars');

		// if this was the same object, then $obj1->var would now be two
		$obj1->var = 'one';
		$obj2->var = 'two';

		$this->assertEquals($obj1->circularOne, $obj2->circularOne);
		$this->assertNotEquals($obj1, $obj2);
	}
	
	public function testCreateWithConstructor() {
		$injector = $this->injector;
		$obj = $injector->create(__NAMESPACE__ . '\\CircularTwo', 'param');
		$this->assertEquals($obj->otherVar, 'param');
	}
	
	public function testSimpleSingleton() {
		$injector = $this->injector;
		
		$one = $injector->create(__NAMESPACE__ . '\\CircularOne');
		$two = $injector->create(__NAMESPACE__ . '\\CircularOne');

		$this->assertFalse($one === $two);
		
		$one = $injector->get(__NAMESPACE__ . '\\CircularTwo');
		$two = $injector->get(__NAMESPACE__ . '\\CircularTwo');
		
		$this->assertTrue($one === $two);
	}

	public function testOverridePriority() {
		$injector = $this->injector;
		$injector->setAutoScanProperties(true);
		$config = array(
			array(
				'src' => __DIR__ . '/Services/SampleService.php',
				'priority' => 10,
			)
		);

		// load
		$injector->load($config);

		// inject
		$myObject = new TestObject();
		$injector->inject($myObject);

		$this->assertEquals(get_class($myObject->sampleService), 'SampleService');

		$config = array(
			array(
				'src' => __DIR__ . '/Services/AnotherService.php',
				'id' => 'SampleService',
				'priority' => 1,
			)
		);
		// load
		$injector->load($config);

		$injector->inject($myObject);
		$this->assertEquals('SampleService', get_class($myObject->sampleService));
	}

	/**
	 * Specific test method to illustrate various ways of setting a requirements backend
	 */
	public function testRequirementsSettingOptions() {
		$injector = $this->injector;
		$config = array(
			__NAMESPACE__ . '\\OriginalRequirementsBackend',
			__NAMESPACE__ . '\\NewRequirementsBackend',
			__NAMESPACE__ . '\\DummyRequirements' => array(
				'constructor' => array(
					'%$' . __NAMESPACE__ . '\\OriginalRequirementsBackend'
				)
			)
		);

		$injector->load($config);

		$requirements = $injector->get(__NAMESPACE__ . '\\DummyRequirements');
		$this->assertEquals(__NAMESPACE__ . '\\OriginalRequirementsBackend', get_class($requirements->backend));

		// just overriding the definition here
		$injector->load(array(
			__NAMESPACE__ . '\\DummyRequirements' => array(
				'constructor' => array(
					'%$' . __NAMESPACE__ . '\\NewRequirementsBackend'
				)
			)
		));

		// requirements should have been reinstantiated with the new bean setting
		$requirements = $injector->get(__NAMESPACE__ . '\\DummyRequirements');
		$this->assertEquals(__NAMESPACE__ . '\\NewRequirementsBackend', get_class($requirements->backend));
	}

	/**
	 * disabled for now
	 */
	public function testStaticInjections() {
		$injector = $this->injector;
		$config = array(
			__NAMESPACE__ . '\\NewRequirementsBackend',
		);

		$injector->load($config);

		$si = $injector->get(__NAMESPACE__ . '\\TestStaticInjections');
		$this->assertEquals(__NAMESPACE__ . '\\NewRequirementsBackend', get_class($si->backend));
	}

	public function testCustomObjectCreator() {
		$injector = $this->injector;
		$injector->setObjectCreator(new SSObjectCreator());
		$config = array(
			__NAMESPACE__ . '\\OriginalRequirementsBackend',
			'DummyRequirements' => array(
				'class' => __NAMESPACE__ . '\\DummyRequirements(\'%$' . __NAMESPACE__ . '\\OriginalRequirementsBackend\')'
			)
		);
		$injector->load($config);

		$requirements = $injector->get('DummyRequirements');
		$this->assertEquals(__NAMESPACE__ . '\\OriginalRequirementsBackend', get_class($requirements->backend));
	}

	public function testInheritedConfig() {
		$injector = $this->injector;
		$injector->get('Config')->update('Injector', __NAMESPACE__ . '\\MyParentClass', array('properties' => array('one' => 'the one')));
		$obj = $injector->get(__NAMESPACE__ . '\\MyParentClass');
		$this->assertEquals($obj->one, 'the one');
		
		$obj = $injector->get(__NAMESPACE__ . '\\MyChildClass');
		$this->assertEquals($obj->one, 'the one');
	}
	
	public function testSameNamedSingletonPrototype() {
		$injector = $this->injector;
		
		// get a singleton object
		$object = $injector->get(__NAMESPACE__ . '\\NeedsBothCirculars');
		$object->var = 'One';
		
		$again = $injector->get(__NAMESPACE__ . '\\NeedsBothCirculars');
		$this->assertEquals($again->var, 'One');
		
		// create a NEW instance object
		$new = $injector->create(__NAMESPACE__ . '\\NeedsBothCirculars');
		$this->assertNull($new->var);
		
		// this will trigger a problem below
		$new->var = 'Two';
		
		$again = $injector->get(__NAMESPACE__ . '\\NeedsBothCirculars');
		$this->assertEquals($again->var, 'One');
	}
}

/**#@+
 * @ignore
 */
class TestObject {

	public $sampleService;

	public function setSomething($v) {
		$this->sampleService = $v;
	}

}

class OtherTestObject {

	private $sampleService;

	public function setSampleService($s) {
		$this->sampleService = $s;
	}

	public function s() {
		return $this->sampleService;
	}

}

class CircularOne {

	public $circularTwo;

}

class CircularTwo {

	public $circularOne;

	public $otherVar;
	
	public function __construct($value = null) {
		$this->otherVar = $value;
	}
}

class NeedsBothCirculars {

	public $circularOne;
	public $circularTwo;
	public $var;

}

class MyParentClass {
	public $one;
}

class MyChildClass extends MyParentClass {
	
}

class DummyRequirements {

	public $backend;

	public function __construct($backend) {
		$this->backend = $backend;
	}

	public function setBackend($backend) {
		$this->backend = $backend;
	}

}

class OriginalRequirementsBackend {

}

class NewRequirementsBackend {

}

class TestStaticInjections {

	public $backend;
	static $dependencies = array(
		'backend' => '%$SilverStripe\\Framework\\Tests\\Injector\\NewRequirementsBackend'
	);

}

/**
 * An example object creator that uses the SilverStripe class(arguments) mechanism for
 * creating new objects
 *
 * @see https://github.com/silverstripe/sapphire
 */
class SSObjectCreator extends InjectionCreator {

	public function create(Injector $injector, $class, $params = array()) {
		if (strpos($class, '(') === false) {
			return parent::create($injector, $class, $params);
		} else {
			list($class, $params) = \Object::parse_class_spec($class);
			return parent::create($injector, $class, $params);
		}
	}

}

/**#@-*/
