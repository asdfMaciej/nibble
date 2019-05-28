<?php
include_once __DIR__ . "/../env/config.php";
include_once ROOT_PATH . "/framework/include.php";

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
		$this->metadata->addStylesheet("style.css");
	}

	protected function header($metadata) {
		$this->response->addTemplate("skeleton/header.php", $metadata);
	}

	protected function footer() {
		$this->response->addTemplate("skeleton/footer.php");
	}

	protected function showSnackbar($message, $code) {
		$this->response->addTemplate("skeleton/snackbar.php", [
			"message" => $message,
			"code" => $code,
		]);
	}
}
?>