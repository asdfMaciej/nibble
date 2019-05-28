<?php
namespace Web\Pages;

class Index extends \ShopBuilder {
	protected function init() {
		$this->metadata->setTitle("Index");
	}

	protected function content() {}
}

new Index();
?>