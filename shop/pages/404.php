<?php
namespace Web\Pages;
use \PDO;

include_once __DIR__ . "/../env/config.php";
include_once ROOT_PATH . "/config/database.php";
include_once ROOT_PATH . "/config/functions.php";
include_once ROOT_PATH . "/config/builder.php";

include_once ROOT_PATH . "/model/basket.php";

class Index extends \WebBuilder {
	protected $basket;

	public function init() {
		$this->basket = new \Model\Basket($this->data);
		$this->history = new \Model\History($this->data);
		$this->metadata->setTitle("404");
	}

	public function content() {
		$this->response->addTemplate("codes/404.php", []);

		$product1 = new \Model\Product();
		$product2 = new \Model\Product();
		$product1->test();
		$product2->test();

		$products = [
			["id" => 11, "slug" => "szafa", "name" => "Szafa", "photo" => "szafa_small.jpg"],
			["id" => 13, "slug" => "kafelek-lazienkowy", "name" => "Kafelek łazienkowy", "photo" => "kafelki.jpg"]
		];

		$products = [
			$product1->asBasketItem(),
			$product2->asBasketItem()
		];

		$this->data->session->basket = [
			"brutto" => 123,
			"netto" => 100,
			"shipment" => 10,
			"products" => $products
		];



		var_dump($this->history->viewed_products);
		var_dump($this->history->clear_date);

		$this->data->session->history = [
			"viewed_products" => [
				["id" => 11, 
				 "seen" => "1553173437", // over
				 "category" => "meble"],

				["id" => 13,
				 "seen" => "1558530237",
				 "category" => "kafelki-lazienkowe"],

				["id" => 100,
				 "seen" => "1558443837",
				 "category" => "meble"]
			],

			"clear_date" => "2019-05-21"
		];

		var_dump($this->basket->total);
		var_dump($this->basket->products);
		var_dump($this->data->path->order);
		var_dump($this->data->path->product);
		var_dump($this->data->path->category);
	}
}

new Index();
?>