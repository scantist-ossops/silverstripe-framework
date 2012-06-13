<?php
/**
 * Extends the default tab with the ability to set a link
 * to an external URL, rather than an anchor on the page,
 * and by this allowing lazy loaded tabs.
 */
class CMSTab extends Tab {

	/**
	 * @var string
	 */
	protected $fieldHolderTemplate = 'CMSTab';

}