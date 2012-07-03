<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Framework\Tests\Util;

use SilverStripe\Framework\Testing\TestCase;
use SilverStripe\Framework\Util\Dag;

/**
 * Tests for the {@link Dag} class.
 *
 * @package framework
 * @subpackage tests
 */
class DagTest extends TestCase {

	public function testSort() {
		$dag = new Dag(array('f', 'd', 'a', 'c', 'b', 'e'));
		$dag->addEdge('a', 'f');
		$dag->addEdge('e', 'f');
		$dag->addEdge('d', 'e');
		$dag->addEdge('a', 'c');
		$dag->addEdge('b', 'c');
		$dag->addEdge('c', 'e');
		$dag->addEdge('c', 'd');
		$dag->addEdge('d', 'f');
		$this->assertEquals(range('a', 'f'), $dag->sort());
	}

	/**
	 * @expectedException \Exception
	 */
	public function testCircularReferences() {
		$dag = new Dag(range('a', 'd'));
		$dag->addEdge('a', 'b');
		$dag->addEdge('b', 'c');
		$dag->addEdge('c', 'd');
		$dag->addEdge('d', 'a');
		$dag->sort();
	}

}
