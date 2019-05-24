<?php
namespace Web\Pages;
use \PDO;
use \Model\CategoryProducts;

include_once __DIR__ . "/../env/config.php";
include_once ROOT_PATH . "/config/builder.php";

class Index extends \ShopBuilder {
	private $category;
	private $products;

	public function init() {
		$this->metadata->setTitle("Sklep - produkty w kategorii");
	}

	public function getCategorySlug() {  // [url]/category/123 -> returns 123
		$category = $this->data->path->category;
		$category = $category !== "" ? $category : 0;
		return $category;
	}

	public function getCategoryAndProducts($category_slug) {
		$where = ["slug" => $category_slug];

		$category_products = new CategoryProducts($this->database);
		$list = $category_products->get($where);

		$category = $list[0]["category"] ?? [];
		$products = [];
		foreach ($list as $group) {
			$products[] = $group["product"];
		}

		$this->category = $category;
		$this->products = $products;
	}

	public function content() {
		$category_slug = $this->getCategorySlug();
		$this->getCategoryAndProducts($category_slug);
		$category_slug = htmlspecialchars($category_slug, ENT_QUOTES, 'UTF-8', false);

		$this->response->addTemplate("category.php", [
			"category" => $this->category,
			"products" => $this->products,
			"category_slug" => $category_slug
		]);

		if ($this->category) {
			$this->history->addCategory($this->category);
		}
	}
}

new Index();
?>