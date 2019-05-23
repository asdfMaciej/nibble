<?php
namespace Web\Pages;
use \PDO;

include_once __DIR__ . "/../env/config.php";
include_once ROOT_PATH . "/config/builder.php";

class Index extends \ShopBuilder {

	public function init() {
		$this->metadata->setTitle("404");
	}

	public function content() {
		$this->response->addTemplate("codes/404.php", []);


		\Model\Product::getProducts($this->database);
	}
}

new Index();
?>