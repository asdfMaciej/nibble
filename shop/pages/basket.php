<?php
namespace Web\Pages;
use \Model\Product;

class Index extends \ShopBuilder {
	private $product;

	protected function init() {
		$this->metadata->setTitle("Sklep - koszyk");
		$this->addAction("remove_item", "onRemoveItem");
	}

	protected function content() {
		$this->response->addTemplate("basket.php", [
			"basket" => $this->basket
		]);
	}

	protected function onRemoveItem() {
		$removed = new Product($this->database);
		$removed->slug = $this->data->post->slug;
		$this->basket->removeProduct($removed);

		$this->snackbar->setMessage("Usunięto z koszyka.");
		$this->snackbar->setCode(200);
	}
}

new Index();
?>