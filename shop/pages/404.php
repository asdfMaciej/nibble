<?php
namespace Web\Pages;
use \Model\Category;

class Index extends \ShopBuilder {
	protected function init() {
		$this->metadata->setTitle("404");
	}

	protected function content() {
		$this->response->addTemplate("codes/404.php", []);
	}
}

new Index();
?>