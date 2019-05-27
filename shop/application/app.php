<?php
include_once __DIR__ . "/../env/config.php";
include_once ROOT_PATH . "/framework/builder.php";

foreach (glob(__DIR__ . "/models/*.php") as $filename) {
    include_once $filename;
}

class ShopBuilder extends \WebBuilder {
	protected $basket;
	protected $history;

	public function __construct() {
		parent::__construct();
		$this->basket = new \Model\Basket($this->data);
		$this->history = new \Model\History($this->data);
	}
}
?>
