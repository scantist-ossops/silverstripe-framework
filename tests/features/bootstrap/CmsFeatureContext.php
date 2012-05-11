<?php
use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

class CmsFeatureContext extends Behat\Mink\Behat\Context\MinkContext {
    /**
     * @When /^I log in with "([^"]*)" and "([^"]*)"$/
     */
    public function iLogInWithAnd($arg1, $arg2)
    {
        throw new PendingException();
    }

    /**
     * @Then /^I will see a bad log-in message$/
     */
    public function iWillSeeABadLogInMessage()
    {
        throw new PendingException();
    }

    /**
     * @Given /^if I visit admin$/
     */
    public function ifIVisitAdmin()
    {
        throw new PendingException();
    }

    /**
     * @Then /^I will be redirected to Security\/login$/
     */
    public function iWillBeRedirectedToSecurityLogin()
    {
        throw new PendingException();
    }
}