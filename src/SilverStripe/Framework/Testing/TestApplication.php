<?php
/**
 * @package framework
 * @subpackage testing
 */

namespace SilverStripe\Framework\Testing;

use SilverStripe\Framework\Core\Application;

/**
 * The base case for silverstripe test cases.
 *
 * @package framework
 * @subpackage testing
 */
class TestApplication extends Application {

	protected $basePath;

	public function getBasePath() {
		return $this->basePath;
	}

	public function setBasePath($basePath) {
		$this->basePath = $basePath;
	}

}
