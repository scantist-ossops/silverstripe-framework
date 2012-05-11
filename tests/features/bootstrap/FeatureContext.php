<?php
require_once dirname(__FILE__) . '/../../../../vendor/autoload.php';

use Behat\Behat\Context\ClosuredContextInterface,
	Behat\Behat\Context\BehatContext,
	Behat\Behat\Exception\PendingException;

use Behat\Gherkin\Node\PyStringNode,
	Behat\Gherkin\Node\TableNode;

class FeatureContext extends Behat\Mink\Behat\Context\MinkContext {
				
}