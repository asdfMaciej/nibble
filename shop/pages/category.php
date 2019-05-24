<?php
namespace Web\Pages;
use \PDO;
use \Model\ProductConnection;

include_once __DIR__ . "/../env/config.php";
include_once ROOT_PATH . "/config/builder.php";

class Index extends \ShopBuilder {
	public function init() {
		$this->metadata->setTitle("Product list");
	}

	public function getCategoryID() {  // [url]/category/123 -> returns 123
		$category = $this->data->path->category;
		$category = $category !== "" ? $category : 0;
		return $category;
	}

	public function getCategoryAndProducts() {
		$category_id = $this->getCategoryID();
		$where = ["category.slug" => $category_id];

		$category_products = new ProductConnection($this->database);
		$list = $category_products->get($where);
		var_dump($list);
		if (count($list) == 0) {
			return [];
		}

		$category = $list[0]["category"];
		$products = [];
		foreach ($list as $group) {
			$products[] = $group["product"];
		}

		$result = [
			"category" => $category,
			"products" => $products
		];
		return $result;
	}



	public function content() {
		var_dump($this->getCategoryAndProducts());
	}
}

new Index();
?>