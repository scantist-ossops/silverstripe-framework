<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class ViewableDataTest extends SapphireTest {
	
	public function testRequiresCasting() {
		$caster = new ViewableDataTest_Castable();
		
		$this->assertTrue($caster->obj('alwaysCasted') instanceof ViewableDataTest_RequiresCasting);
		$this->assertTrue($caster->obj('noCastingInformation') instanceof ViewableData_Caster);
		
		$this->assertTrue($caster->obj('alwaysCasted', null, false) instanceof ViewableDataTest_RequiresCasting);
		$this->assertFalse($caster->obj('noCastingInformation', null, false) instanceof ViewableData_Caster);
	}

	public function testFailoverRequiresCasting() {
		$caster = new ViewableDataTest_Castable();
		$container = new ViewableDataTest_Container($caster);

		$this->assertTrue($container->obj('alwaysCasted') instanceof ViewableDataTest_RequiresCasting);
		$this->assertTrue($caster->obj('alwaysCasted', null, false) instanceof ViewableDataTest_RequiresCasting);

		/* @todo - This currently fails, because the default_cast static variable is always taken from the topmost object,
		 * not the failover object the field actually came from. Should we fix this, or declare current behaviour as correct?

		$this->assertTrue($container->obj('noCastingInformation') instanceof ViewableData_Caster);
		$this->assertFalse($caster->obj('noCastingInformation', null, false) instanceof ViewableData_Caster);
		*/
	}

	public function testCastingXMLVal() {
		$caster = new ViewableDataTest_Castable();
		
		$this->assertEquals('casted', $caster->XML_val('alwaysCasted'),
			'Respects explicit casting'
		);
		$this->assertEquals('casted', $caster->XML_val('noCastingInformation'),
			'Falls back to $default_cast if no casting info is provided'
		);
		
		// test automatic escaping is only applied by casted classes
		$this->assertEquals('casted', $caster->XML_val('unsafeXML'));
		$this->assertEquals('&lt;foo&gt;', $caster->XML_val('castedUnsafeXML'));
	}
	
	public function testUncastedXMLVal() {
		$caster = new ViewableDataTest_Castable();
		$this->assertEquals($caster->XML_val('uncastedZeroValue'), 'casted');

		$caster = new ViewableDataTest_CastableNoDefaults();
		$this->assertEquals($caster->XML_val('uncastedZeroValue'), 0);
	}
	
	public function testArrayCustomise() {
		$viewableData    = new ViewableDataTest_CastableNoDefaults();
		$newViewableData = $viewableData->customise(array (
			'test'         => 'overwritten',
			'alwaysCasted' => 'overwritten'
		));
		
		$this->assertEquals('test', $viewableData->XML_val('test'));
		$this->assertEquals('casted', $viewableData->XML_val('alwaysCasted'));
		
		$this->assertEquals('overwritten', $newViewableData->XML_val('test'));
		$this->assertEquals('overwritten', $newViewableData->XML_val('alwaysCasted'));
	}
	
	public function testObjectCustomise() {
		$viewableData    = new ViewableDataTest_Castable();
		$newViewableData = $viewableData->customise(new ViewableDataTest_RequiresCasting());
		
		$this->assertEquals('test', $viewableData->XML_val('test'));
		$this->assertEquals('casted', $viewableData->XML_val('alwaysCasted'));
		
		$this->assertEquals('overwritten', $newViewableData->XML_val('test'));
		$this->assertEquals('casted', $newViewableData->XML_val('alwaysCasted'));
	}
	
	public function testRAWVal() {
		$data = new ViewableDataTest_Castable();
		$data->test = 'This &amp; This';
		$this->assertEquals($data->RAW_val('test'), 'This & This');
	}
	
	public function testSQLVal() {
		$data = new ViewableDataTest_Castable();
		$this->assertEquals($data->SQL_val('test'), 'test');
	}

	public function testJSVal() {
		$data = new ViewableDataTest_Castable();
		$data->test = '"this is a test"';
		$this->assertEquals($data->JS_val('test'), '\"this is a test\"');
	}

	public function testATTVal() {
		$data = new ViewableDataTest_Castable();
		$data->test = '"this is a test"';
		$this->assertEquals($data->ATT_val('test'), '&quot;this is a test&quot;');
	}

	public function testCastingClass() {
		$expected = array(
			'NonExistant'   => null,
			'Field'         => 'CastingType',
			'Argument'      => 'ArgumentType',
			'ArrayArgument' => 'ArrayArgumentType'
		);
		$obj = new ViewableDataTest_CastingClass();

		foreach($expected as $field => $class) {
			$this->assertEquals(
				$class,
				$obj->castingClass($field),
				"castingClass() returns correct results for ::\$$field"
			);
		}
	}

	function testFirstLast() {
		$vd = new ViewableData();
		
		$vd->iteratorProperties(0, 3);
		$this->assertEquals('first', $vd->FirstLast());

		$vd->iteratorProperties(1, 3);
		$this->assertEquals(null, $vd->FirstLast());

		$vd->iteratorProperties(2, 3);
		$this->assertEquals('last', $vd->FirstLast());

		$vd->iteratorProperties(0, 1);
		$this->assertEquals('first last', $vd->FirstLast());
	}

	function testTemplateCastingOnTextDbField() {
		$field = DBField::create('Text', '<script>');

		$tests = array(
			'$MyField' => '<script>',
			'$MyField.LimitCharacters(100)' => '<script>',
			'$MyField.XML' => '<script>',
			'$MyField.RAW' => '<script>',
		);
		foreach($tests as $template => $expected) {
			$viewer = SSViewer::fromString('');
			$actual = $viewer->process(new ArrayData(array('MyField' => $field)));
			$this->assertEquals(
				$expected, 
				$actual,
				sprintf('Correctly casts Text value for template "%s"', $template)
			);	
		}
	}

	function testTemplateCastingWithUnescapedInput() {
		foreach(array('LimitCharacters(100)', 'LimitWordCount(100)') as $method) {
			$viewer = SSViewer::fromString('$MyField.' . $method);
			$html = $viewer->process(new ArrayData(array('MyField' => DBField::create('Text', '<script>'))));
			$this->assertEquals(
				$html, 
				'&lt;script&gt;', 
				'Correctly escapes HTML entities for method: ' . $method
			);			
		}
	}

}

/**#@+
 * @ignore
 */

class ViewableDataTest_Castable extends ViewableData {
	
	public static $default_cast = 'ViewableData_Caster';
	
	public static $casting = array (
		'alwaysCasted'    => 'ViewableDataTest_RequiresCasting',
		'castedUnsafeXML' => 'ViewableData_UnescaptedCaster'
	);
	
	public $test = 'test';
	
	public $uncastedZeroValue = 0;
	
	public function alwaysCasted() {
		return 'alwaysCasted';
	}
	
	public function noCastingInformation() {
		return 'noCastingInformation';
	}
	
	public function unsafeXML() {
		return '<foo>';
	}
	
	public function castedUnsafeXML() {
		return $this->unsafeXML();
	}
	
}

class ViewableDataTest_CastableNoDefaults extends ViewableDataTest_Castable {
	public static $default_cast = null;
}

class ViewableDataTest_RequiresCasting extends ViewableData {
	
	public $test = 'overwritten';
	
	public function forTemplate() {
		return 'casted';
	}
	
	public function setValue() {}
	
}

class ViewableData_UnescaptedCaster extends ViewableData {
	
	protected $value;
	
	public function setValue($value) {
		$this->value = $value;
	}
	
	public function forTemplate() {
		return Convert::raw2xml($this->value);
	}
	
}

class ViewableData_Caster extends ViewableData {
	
	public function forTemplate() {
		return 'casted';
	}
	
	public function setValue() {}
	
}

class ViewableDataTest_Container extends ViewableData {

	public function __construct($failover) {
		$this->failover = $failover;
		parent::__construct();
	}
}

class ViewableDataTest_CastingClass extends ViewableData {
	public static $casting = array(
		'Field'         => 'CastingType',
		'Argument'      => 'ArgumentType(Argument)',
		'ArrayArgument' => 'ArrayArgumentType(array(foo, bar))'
	);
}

/**#@-*/