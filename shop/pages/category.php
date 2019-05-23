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
		/*
		$m = new \Model\ProductDB($this->database);//Product::getProducts($this->database);
		$m->key_pl = "kafelek";
		$m->symbol = "Kafelek";
		$m->name_pl = "Kafelek";
		$m->desc_pl = "KAFELEK!!!";
		$m->stock = 2138;
		$m->vat = 23;
		$m->priceAn = 200;
		$m->priceAg = 246;
		$m->priceBn = 100;
		$m->priceBg = 123;
		$m->fotos = "kafelki.jpg";
		$m->id = 7;
		var_dump($m->getFields());
		
		var_dump($m->save());*/
		/*
		$aaa = ["a" => 123, "b" => 3333];
		$m2 = \Model\DBModel::fromArray($this->database, $aaa);
		var_dump($m2);

		$products = \Model\Product::getProducts($this->database);
		*/
		$x = new \Model\QueryBuilder();
		$x->model = \Model\ProductConnection::class;
		$x->join = [[
			"model" => \Model\ProductDB::class,
			"key" => "product_connection.pid",
			"method" => "left"
		]];

		$a = new \Model\ProductConnection($this->database);
		$where = [];
		$b = new \Model\ProductDB($this->database);
		$res = $b->get();
		$product = $res[0]["product"];
		//var_dump($product);
		//echo $product->
		var_dump($product->name);
		var_dump($product->name_pl);
		var_dump($product->niema);
		$product->name = 123;
		var_dump($product->name);
		var_dump($product->name_pl);
		$product->name_pl = 321;
		var_dump($product->name);
		var_dump($product->name_pl);
		//var_dump($a->get($where));
		//$a->fetch();

		//$this->response->addTemplate("text.php", ["text" => $this->getCategory()]);
		//$this->response->addTemplate("products_list.php", ["products" => $products]);
	}
}

new Index();
?>