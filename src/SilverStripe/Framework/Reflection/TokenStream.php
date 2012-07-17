<?php
/**
 * @package    framework
 * @subpackage reflection
 */

namespace SilverStripe\Framework\Reflection;

/**
 * A wrapper around a list of tokens that exposes a nicer API for stepping
 * through them.
 *
 * This class also normalises tokens, as well as adding basic support for
 * parsing traits in PHP versions less than 5.4.
 *
 * @package    framework
 * @subpackage reflection
 */
class TokenStream {

	protected $source;
	protected $tokens = array();

	protected $position = 0;
	protected $count;

	/**
	 * Constructs a new token stream instance from a string of PHP code.
	 *
	 * @param string $source
	 */
	public function __construct($source) {
		if(!extension_loaded('tokenizer')) {
			throw new \Exception('The tokenizer extension is not loaded.');
		}

		$emulateTraits = !defined('T_TRAIT');

		foreach(token_get_all($source) as $key => $token) {
			if(is_array($token)) {
				if($emulateTraits && $token[0] == T_STRING && $token[1] == 'trait') {
					$token[0] = -1;
				}

				$this->tokens[] = $token;
			} else {
				$this->tokens[] = array($token, $token);
			}
		}

		$this->source = $source;
		$this->count  = count($this->tokens);
	}

	/**
	 * @return string
	 */
	public function getSource() {
		return $this->source;
	}

	/**
	 * @return array
	 */
	public function getTokens() {
		return $this->tokens;
	}

	/**
	 * Returns TRUE if the token type at the current position matches the
	 * parameter.
	 *
	 * @param  int|string $token
	 * @return bool
	 */
	public function is($token) {
		if(is_int($token)) {
			return $this->getToken() == $token;
		} else {
			return $this->getName() == $token;
		}
	}

	/**
	 * Skips ahead until a non-whitespace token is encountered.
	 */
	public function next() {
		do {
			$this->position++;
		} while(!$this->finished() && $this->isWhitespace());
	}

	/**
	 * @return bool
	 */
	public function finished() {
		return $this->position >= $this->count;
	}

	/**
	 * Returns the token code or type at the current position.
	 *
	 * @return int|string
	 */
	public function getToken() {
		if(isset($this->tokens[$this->position])) {
			return $this->tokens[$this->position][0];
		}
	}

	/**
	 * Returns the token value at the current position.
	 *
	 * @return string
	 */
	public function getValue() {
		if(isset($this->tokens[$this->position])) {
			return $this->tokens[$this->position][1];
		}
	}

	/**
	 * Returns a strig representation of the token type at the current position.
	 *
	 * @return string
	 */
	public function getName() {
		$T_TRAIT = defined('T_TRAIT') ? T_TRAIT : -1;

		if($token = $this->getToken()) {
			if($token == $T_TRAIT) {
				return 'T_TRAIT';
			} elseif(is_string($token)) {
				return $token;
			} else {
				return token_name($token);
			}
		}
	}

	/**
	 * @return int
	 */
	public function getPosition() {
		return $this->position;
	}

	/**
	 * Returns TRUE if the token at the current position is whitespace or a
	 * comment.
	 *
	 * @return bool
	 */
	public function isWhitespace() {
		return $this->is(T_WHITESPACE) || $this->is(T_COMMENT) || $this->is(T_DOC_COMMENT);
	}

}
