<?php
namespace Model;
class Basket extends \SessionModel {
	private const SHIPMENT = 10;
	protected $fields = [
		"netto" => 0,
		"brutto" => 0,
		"shipment" => 0,
		"products" => []
	];
	
	public function init() {
		$this->computed = [
			"total" => function() {
				return $this->brutto + $this->shipment;
			},

			"empty" => function() {
				return count($this->products) === 0;
			}
		];
	}

	public function addProduct($product) {
		$item = $product->asBasketItem();
		if ($this->empty) {
			$this->shipment = Basket::SHIPMENT;
		}
		$this->netto += $item["price_netto"];
		$this->brutto += $item["price_brutto"];
		$this->products[] = $item;
	}

	public function removeProduct($product) {
		$slug = $product->slug;
		foreach ($this->products as $i => $basketProduct) {
			if ($basketProduct["slug"] == $slug) {
				$this->netto -= $basketProduct["price_netto"];
				$this->brutto -= $basketProduct["price_brutto"];
				unset($this->products[$i]);
				break;
			}
		}

		if ($this->empty) {
			$this->shipment = 0;
		}
	}

	public function clear() {
		$this->netto = 0;
		$this->brutto = 0;
		$this->shipment = 0;
		$this->products = [];
	}
}
?>