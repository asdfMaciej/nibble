<?php
namespace Model;
use \PDO;

class Product {
	public $id;
	public $slug;
	public $symbol;
	public $name;
	public $description;
	public $visible;
	public $stock;
	public $vat;

	public $price_normal = [
		"netto" => null,
		"brutto" => null
	];

	public $price_discounted = [
		"netto" => null,
		"brutto" => null
	];

	public $photos = [
		"small" => null,
		"medium" => null,
		"big" => null
	];

	public $purchase_count;
	public $view_count;
	public $view_date;
	public $creation_date;

	// for basket usage:
	public $price = null;
	public $added_time = null;

	public function test() {
		$this->id = 13;
		$this->slug = "kafelek-lazienkowy";
		$this->symbol = "K4F3LK1";
		$this->name = "Kafelek łazienkowy";
		$this->description = "Poczuj się jak w łazience.";
		$this->visible = 'on';
		$this->stock = 2138;
		$this->vat = 23;
		$this->price_normal["netto"] = 200;
		$this->price_normal["brutto"] = 246;
		$this->price_discounted["netto"] = 100;
		$this->price_discounted["brutto"] = 123;

		$this->photos["small"] = "kafelki.jpg";
		$this->purchase_count = 50;
		$this->view_count = 3000;
		$this->view_date = "";
		$this->creation_date = "";
	}

	public function getPrice($tax="brutto", $discounted=False) {
		$prices = $discounted ? 
					$this->price_discounted :
					$this->price_normal;
		$price = $prices[$tax];
		return $price;
	}

	public function asBasketItem() {
		$price = $this->price ?? $this->getPrice();
		$added_time = $this->added_time ?? strtotime("now");

		$item = [
			"id" => $this->id,
			"slug" => $this->slug,
			"name" => $this->name,
			"price" => $price,
			"photo" => $this->photos["small"],
			"symbol" => $this->symbol,
			"added" => $added_time
		];

		return $item;
	}

	public function fromBasketItem($item) {
		$this->id = $item["id"];
		$this->slug = $item["slug"];
		$this->name = $item["name"];
		$this->price = $item["price"];
		$this->photos["small"] = $item["photo"];
		$this->symbol = $item["symbol"];
		$this->added_time = $item["added"];
	}
}

class Basket {
	private $basket;

	public $netto;
	public $brutto;
	public $shipment;
	public $products;
	public $total;

	public function __construct($data) {
		$this->basket = $data->session->basket;

		$this->netto = floatval($this->basket["netto"] ?? 0);
		$this->brutto = floatval($this->basket["brutto"] ?? 0);
		$this->shipment = floatval($this->basket["shipment"] ?? 0);
		$this->products = $this->basket["products"] ?? [];

		$this->total = $this->brutto + $this->shipment; 
	}
}

class History {
	private $data;

	public $viewed_products;
	public $clear_date;

	public function __construct($data) {
		$this->data = $data;

		$this->update();
		$today = date('Y-m-d');
		if ($today !== $this->clear_date) {
			$this->clearHistory();
		}
	}

	private function update() {
		$history = $this->data->session->history;
		$this->viewed_products = $history["viewed_products"] ?? []; 
		$this->clear_date = $history["clear_date"] ?? "";
	}

	private function clearHistory() {
		$month_ago = strtotime("-1 month");
		$remove_from_start = 0;
		foreach ($this->viewed_products as $product) {
			/* binary search would be faster */
			$seen = intval($product["seen"]);
			if ($seen <= $month_ago) {
				$remove_from_start += 1;
			} else {
				break;
			}
		}

		$viewed = array_slice($this->viewed_products, $remove_from_start);
		$today = date('Y-m-d');
		$_SESSION["history"]["viewed_products"] = $viewed;
		$_SESSION["history"]["clear_date"] = $today;
		$this->update();
	}
}
?>
