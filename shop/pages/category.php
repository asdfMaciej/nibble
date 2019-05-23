<?php
namespace Web\Pages;
use \PDO;

include_once __DIR__ . "/../env/config.php";
include_once ROOT_PATH . "/config/builder.php";

class Index extends \ShopBuilder {

	public function init() {
		$this->metadata->setTitle("Product list");
	}

	public function getCategory() {
		$category = $this->data->path->category;
		$category = $category !== "" ? $category : 0;
		return $category;
	}

	public function content() {
		$m = new \Model\ProductDB($this->database);//Product::getProducts($this->database);
		$m->key_pl = "kafelek-lazien345345kowy-2";
		$m->symbol = "K4F3LK13345";
		$m->name_pl = "Kafelek łazienk345345owyyyyy";
		$m->desc_pl = "Poczuj się jak345345345 w łazience. yyy";
		$m->stock = 2138;
		$m->vat = 23;
		$m->priceAn = 200;
		$m->priceAg = 246;
		$m->priceBn = 100;
		$m->priceBg = 123;
		$m->fotos = "kafelki.jpg";
		$m->id = 7;

		var_dump($m->save());
		$aaa = ["a" => 123, "b" => 3333];
		$m2 = \Model\DBModel::fromArray($this->database, $aaa);
		var_dump($m2);

		$products = \Model\Product::getProducts($this->database);
		$this->response->addTemplate("text.php", ["text" => $this->getCategory()]);
		$this->response->addTemplate("products_list.php", ["products" => $products]);
	}
}

new Index();
?>