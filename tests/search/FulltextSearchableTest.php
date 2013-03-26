<?php
/**
 * @package framework
 * @subpackage tests
 */

class FulltextSearchableTest extends SapphireTest {

	protected $illegalExtensions = array(
		'File' => array('FulltextSearchable')
	);

	public function testEnable() {
		FulltextSearchable::enable();
		$this->assertTrue(File::has_extension('FulltextSearchable'));
	}
	
	public function testEnableWithCustomClasses() {
		FulltextSearchable::enable(array('File'));
		$this->assertTrue(File::has_extension('FulltextSearchable'));

		// TODO This shouldn't need all arguments included
		File::remove_extension('FulltextSearchable(\'"Filename","Title","Content"\')');
		
		$this->assertFalse(File::has_extension('FulltextSearchable'));
	}
	
}
