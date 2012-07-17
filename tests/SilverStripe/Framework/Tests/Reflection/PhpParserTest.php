<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Framework\Tests\Reflection;

use SilverStripe\Framework\Reflection\PhpParser;
use SilverStripe\Framework\Reflection\TokenStream;
use SilverStripe\Framework\Testing\TestCase;

/**
 * Tests for the {@link PhpParser} class.
 *
 * @package framework
 * @subpackage tests
 */
class PhpParserTest extends TestCase {

	public function testBasic() {
		$source = file_get_contents(__DIR__ . '/fixtures/parser/Basic.php');
		$parser = new PhpParser(new TokenStream($source));
		$parser->parse();

		$this->assertEquals($parser->getClasses(), array(
			array('name' => 'N\A', 'extends' => null, 'implements' => array()),
			array('name' => 'N\D', 'extends' => 'N\A', 'implements' => array('N\B'))
		));

		$this->assertEquals($parser->getInterfaces(), array(
			array('name' => 'N\B', 'extends' => array())
		));

		$this->assertEquals($parser->getTraits(), array(
			'N\C'
		));
	}

	public function testComplex() {
		$source = file_get_contents(__DIR__ . '/fixtures/parser/Complex.php');
		$parser = new PhpParser(new TokenStream($source));
		$parser->parse();

		$this->assertEquals($parser->getClasses(), array(
			array('name' => 'N1\A', 'extends' => 'U2', 'implements' => array('U2', 'N1\C')),
			array('name' => 'N2\F', 'extends' => 'U2\C', 'implements' => array()),
		));

		$this->assertEquals($parser->getInterfaces(), array(
			array('name' => 'N1\D', 'extends' => array('U2', 'B')),
		));

		$this->assertEquals($parser->getTraits(), array(
			'N1\E'
		));
	}

}
