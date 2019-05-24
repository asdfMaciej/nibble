<?php
namespace Web\Pages;
use \PDO;
use \Model\Product;

include_once __DIR__ . "/../env/config.php";
include_once ROOT_PATH . "/config/builder.php";

class Index extends \ShopBuilder {
	private $product;

	public function init() {
		$this->metadata->setTitle("Sklep - produkt");
	}

	public function getProductSlug() {
		$product = $this->data->path->product;
		return $product !== "" ? $product : 0;
	}

	public function getProduct($product_slug) {
		$where = ["slug" => $product_slug];

		$product = new Product($this->database);
		$result = $product->get($where);
		if ($result) {
			$this->product = $result[0]["product"];
		} else {
			$this->product = [];
		}
	}

	public function content() {
		$product_slug = $this->getProductSlug();
		$this->getProduct($product_slug);

		$product_slug = htmlspecialchars($product_slug, ENT_QUOTES, 'UTF-8', false);

		$this->response->addTemplate("product.php", [
			"product" => $this->product,
			"product_slug" => $product_slug
		]);

		if ($this->product) {
			$this->history->addProduct($this->product);
		}
	}
}

new Index();
?>