<?php
namespace Web\Pages;
use \Model\Product;

class Index extends \ShopBuilder {
	private $product;

	protected function init() {
		$this->metadata->setTitle("Sklep - produkt");
		$this->addAction("add_basket", "onAddBasket");

		$product_slug = $this->getProductSlug();
		$this->getProduct($product_slug);
	}

	protected function getProductSlug() {
		$product = $this->data->path->product;
		return $product !== "" ? $product : 0;
	}

	protected function getProduct($slug) {
		$this->product = Product::getProduct($this->database, $slug);
	}

	protected function onAddBasket() {
		$this->basket->addProduct($this->product);
		$this->snackbar->setMessage("Dodano do koszyka");
		$this->snackbar->setCode(200);
	}

	protected function content() {
		$this->response->addTemplate("product.php", [
			"product" => $this->product,
			"product_slug" => $this->getProductSlug()
		]);

		if ($this->product) {
			$this->history->addProduct($this->product);
		}
	}
}

new Index();
?>