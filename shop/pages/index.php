<?php
namespace Web\Pages;

class Index extends \ShopBuilder {
	protected function init() {
		$this->metadata->setTitle("Index");
	}

	protected function content() {
		var_dump($_SESSION);
	}
}

new Index();
?>