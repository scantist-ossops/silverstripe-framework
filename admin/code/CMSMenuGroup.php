<?php
class CMSMenuGroup extends CMSMenuItem {

	/**
	 * @var DataObjectSet
	 */
	protected $items;
	
	/**
	 * @param DataObjectSet
	 * @param String
	 * @param String
	 * @param Controller
	 * @param Int
	 */
	public function __construct($items, $title, $url = null, $controller = null, $priority = -1) {
		$this->items = $items;

		parent::__construct($title, $url, $controller, $priority);
	}
	
	/**
	 * @return DataObjectSet
	 */
	function getItems() {
		return $this->items;
	}
}