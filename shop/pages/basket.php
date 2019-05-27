<?php
namespace Web\Pages;
use \Model\Product;

include_once ROOT_PATH . "/application/app.php";

class Index extends \ShopBuilder {
	private $product;

	public function init() {
		$this->metadata->setTitle("Sklep - koszyk");
		$this->addAction("remove_item", "onRemoveItem");
	}

	public function content() {
		$this->response->addTemplate("basket.php", [
			"basket" => $this->basket
		]);
	}

	public function onRemoveItem() {
		$removed = new Product($this->database);
		$removed->slug = $this->data->post->slug;
		$this->basket->removeProduct($removed);
		$this->snackbar->setMessage("Usunięto z koszyka.");
		$this->snackbar->setCode(200);
	}
}

new Index();
?>