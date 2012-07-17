<h1>This complex example has some HTML at the start</h1>

<?php
namespace N1;
use U1, U2 as B;

final class A extends \U2 implements B, C {
	abstract public function __construct() {
		$example = function($x, $y) use ($z) {};
	}
}

interface D extends B, \B {
}

trait E {
}

namespace N2;

abstract class F extends B\C {
}
