<?php
/**
 * @package framework
 * @subpackage tests
 */

namespace SilverStripe\Framework\Tests\Reflection;

use SilverStripe\Framework\Reflection\TokenStream;
use SilverStripe\Framework\Testing\TestCase;

/**
 * Tests for the {@link TokenStream} class.
 *
 * @package framework
 * @subpackage tests
 */
class TokenStreamTest extends TestCase {

	public function testGetSource() {
		$source = 'test';
		$stream = new TokenStream($source);

		$this->assertEquals($source, $stream->getSource());
	}

	public function testGetTokens() {
		$source = 'test';
		$expect = token_get_all($source);

		$stream = new TokenStream($source);
		$this->assertEquals($expect, $stream->getTokens());
	}

	public function testTokenNormalisation() {
		$source = '<?php ; trait';
		$stream = new TokenStream($source);
		$tokens = $stream->getTokens();

		$T_TRAIT = defined('T_TRAIT') ? T_TRAIT : -1;

		$this->assertEquals(T_OPEN_TAG, $tokens[0][0]);
		$this->assertEquals(';', $tokens[1][0]);
		$this->assertEquals(T_WHITESPACE, $tokens[2][0]);
		$this->assertEquals($T_TRAIT, $tokens[3][0]);
	}

	public function testIs() {
		$source = '<?php test();';
		$stream = new TokenStream($source);

		$this->assertTrue($stream->is(T_OPEN_TAG));
		$this->assertFalse($stream->is(T_WHITESPACE));

		$stream->next();

		$this->assertTrue($stream->is(T_STRING));
		$this->assertFalse($stream->is(T_OPEN_TAG));
	}

	public function testNext() {
		$source = '<?php /* comment */ use';
		$stream = new TokenStream($source);

		$this->assertTrue($stream->is(T_OPEN_TAG));
		$stream->next();
		$this->assertTrue($stream->is(T_USE), 'The next method skips whitespace');
	}

	public function testFinished() {
		$source = '<?php one';
		$stream = new TokenStream($source);

		$this->assertFalse($stream->finished());
		$stream->next();
		$this->assertFalse($stream->finished());
		$stream->next();
		$this->assertTrue($stream->finished());
	}

	public function testGetters() {
		$source = '<?php ; trait';
		$stream = new TokenStream($source);

		$this->assertEquals(T_OPEN_TAG, $stream->getToken());
		$this->assertEquals('<?php ', $stream->getValue());
		$this->assertEquals('T_OPEN_TAG', $stream->getName());
		$this->assertEquals(0, $stream->getPosition());

		$stream->next();

		$this->assertEquals(';', $stream->getToken());
		$this->assertEquals(';', $stream->getValue());
		$this->assertEquals(';', $stream->getName());
		$this->assertEquals(1, $stream->getPosition());

		$stream->next();

		$T_TRAIT = defined('T_TRAIT') ? T_TRAIT : -1;

		$this->assertEquals($T_TRAIT, $stream->getToken());
		$this->assertEquals('trait', $stream->getValue());
		$this->assertEquals('T_TRAIT', $stream->getName());
		$this->assertEquals(3, $stream->getPosition());

		$stream->next();

		$this->assertNull($stream->getToken());
		$this->assertNull($stream->getValue());
		$this->assertNull($stream->getName());
		$this->assertEquals(4, $stream->getPosition());
	}

}
