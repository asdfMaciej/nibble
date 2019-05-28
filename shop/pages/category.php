<?php
namespace Web\Pages;
use \PDO;
use \Model\CategoryProducts;
use \Model\Category;

class Index extends \ShopBuilder {
	private $category = [];
	private $products;
	private $child_categories;

	protected function init() {
		$this->metadata->setTitle("Sklep - produkty w kategorii");
		$slug = $this->getCategorySlug();
		$this->getProducts($slug);
		$this->getCategory($slug);
		$this->getChildCategories($slug);
	}

	protected function getCategorySlug() {
		$category = $this->data->path->category;
		$category = $category !== "" ? $category : "";
		return $category;
	}

	protected function getProducts($slug) {
		$this->products = Category::getProducts($this->database, $slug);
	}

	protected function getCategory($slug) {
		$this->category = Category::getCategory($this->database, $slug);
	}

	protected function getChildCategories($slug) {
		$this->child_categories = Category::getChildCategories($this->database, $slug);
	}

	protected function content() {
		$this->response->addTemplate("category.php", [
			"category" => $this->category,
			"products" => $this->products,
			"child_categories" => $this->child_categories,
			"category_slug" => $this->getCategorySlug()
		]);

		if ($this->category) {
			$this->history->addCategory($this->category);
		}
	}
}

new Index();
?>