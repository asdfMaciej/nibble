<?php
namespace Model;
class OrderProduct extends \DBModel {
	protected static $table_name = "order_product";
	protected static $primary_key = "id";

	public $id, $product_id, $order_id, 
		$name, $quantity, $pricen, $priceg, $vat;

	protected static $aliases = [
		"netto" => "pricen",
		"brutto" => "priceg"
	];
}
?>