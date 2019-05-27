<?php
namespace Web\Pages;
use \PDO;
use \Model\CategoryProducts;
use \Model\Category;


include_once __DIR__ . "/../env/config.php";
include_once ROOT_PATH . "/config/builder.php";

class Index extends \ShopBuilder {
	private $category = [];
	private $products;
	private $child_categories;

	public function init() {
		$this->metadata->setTitle("Sklep - produkty w kategorii");
		$slug = $this->getCategorySlug();
		$this->getProducts($slug);
		$this->getCategory($slug);
		$this->getChildCategories($slug);
	}

	public function getCategorySlug() {
		$category = $this->data->path->category;
		$category = $category !== "" ? $category : "";
		return $category;
	}

	public function getProducts($slug) {
		$this->products = Category::getProducts($this->database, $slug);
	}

	public function getCategory($slug) {
		$this->category = Category::getCategory($this->database, $slug);
	}

	public function getChildCategories($slug) {
		$this->child_categories = Category::getChildCategories($this->database, $slug);
	}

	public function content() {
		$category_slug = $this->getCategorySlug();
		$category_slug = htmlspecialchars($category_slug, ENT_QUOTES, 'UTF-8', false);

		$this->response->addTemplate("category.php", [
			"category" => $this->category,
			"products" => $this->products,
			"child_categories" => $this->child_categories,
			"category_slug" => $category_slug
		]);

		if ($this->category) {
			$this->history->addCategory($this->category);
		}
	}
}

new Index();
?>