<?php
namespace Model;

class Product extends \DBModel {
	protected static $table_name = "product";
	protected static $primary_key = "id";

	protected static $aliases = [
		"slug" => "key_pl",
		"name" => "name_pl",
		"desc" => "desc_pl",
		"price_normal_netto" => "priceAn",
		"price_discounted_netto" => "priceBn",
		"price_normal_brutto" => "priceAg",
		"price_discounted_brutto" => "priceBg",
		"photo_small" => "fotos",
		"photo_medium" => "fotom",
		"photo_big" => "fotob",
		"creation_date" => "cdate"
	];

	public $id;
	public $key_pl;
	public $symbol;
	public $name_pl;
	public $desc_pl;
	public $stock;
	public $vat;

	public $priceAn;
	public $priceBn;
	public $priceAg;
	public $priceBg;

	public $fotos;
	public $fotom;
	public $fotob;

	public $purchase_count;
	public $view_count;
	public $view_date;
	public $cdate;

	/* DB boilerplate above */

	protected $basket_fields = [
		"netto" => null,
		"brutto" => null,
		"added_time" => null
	];

	public function getBasketFields() {
		return $this->basket_fields;
	}

	public function setBasketFields($basket) {
		$this->basket_fields = $basket;
	}

	public function test() {
		$this->id = 13;
		$this->slug = "kafelek-lazienkowy";
		$this->symbol = "K4F3LK1";
		$this->name = "Kafelek łazienkowy";
		$this->description = "Poczuj się jak w łazience.";
		$this->stock = 2138;
		$this->vat = 23;
		$this->price_normal_netto = 200;
		$this->price_normal_brutto = 246;
		$this->price_discounted_netto = 100;
		$this->price_discounted_brutto = 123;

		$this->photo_small = "kafelki.jpg";
		$this->purchase_count = 50;
		$this->view_count = 3000;
	}

	public function getPrice($tax="brutto", $discounted=False) {
		if ($discounted) {
			return $tax == "netto" ? 
				$this->price_discounted_netto : 
				$this->price_discounted_brutto;
		}
		return $tax == "netto" ? 
			$this->price_normal_netto : 
			$this->price_normal_brutto;
	}

	public static function getProduct($db, $slug) {
		return static::getSingleItem($db, ["slug" => $slug]);
	}

	public function isEmpty() {
		return is_null($this->id);
	}

	public function asBasketItem() {
		$price_netto = $this->basket_fields["netto"] ?? $this->getPrice("netto");
		$price_brutto = $this->basket_fields["brutto"] ?? $this->getPrice("brutto");
		$added_time = $this->basket_fields["added_time"] ?? strtotime("now");

		$item = [
			"id" => $this->id,
			"slug" => $this->slug,
			"name" => $this->name,
			"price_netto" => $price_netto,
			"price_brutto" => $price_brutto,
			"photo_small" => $this->photo_small,
			"symbol" => $this->symbol,
			"added_time" => $added_time,
			"vat" => $this->vat
		];

		return $item;
	}

	public function asHistoryItem() {
		$item = [
			"id" => $this->id,
			"slug" => $this->slug,
			"name" => $this->name,
			"seen_on" => strtotime("now") 
		];

		return $item;
	}

	public static function fromHistoryItem($db, $item) {
		return static::getSingleItem($db, ["id" => $item["id"]]);
	}

	public static function fromBasketItem($item) {
		$product = new Product();

		$fields = ["id", "slug", "name", "symbol", "photo_small"];
		foreach ($fields as $field) {
			$product->{$field} = $item[$field];
		}

		$basket = $product->getBasketFields();

		$basket["price_netto"] = $item["price_netto"];
		$basket["price_brutto"] = $item["price_brutto"];
		$basket["added_time"] = $item["added_time"];

		$product->setBasketFields($basket);

		return $product;
	}
}
?>