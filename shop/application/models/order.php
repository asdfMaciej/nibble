<?php
namespace Model;
class Order extends \DBModel {
	protected static $table_name = "order";
	protected static $primary_key = "id";

	public $id, $customer_id, $date, $basket_amount, 
		$basket_amount_net, $order_amount, $adress, $ip;

	protected static $aliases = [
		"netto" => "basket_amount_net",
		"brutto" => "basket_amount",
		"total" => "order_amount",
		"address" => "adress", // [sic!] not my db
	];

	public static function getOrders($db) {
		$rows = static::select("o.*")
					->from(static::class, "o")
					->orderBy('o.id', 'desc')
					->execute($db)
					->getAll();

		$orders = [];
		foreach ($rows as $row) {
			$orders[] = static::fromArray($row);
		}
		return $orders;
	}

	public static function getOrder($db, $order_id) {
		$row = static::select("o.*")
					->from(static::class, "o")
					->where('o.id = :id')
					->setParameter(':id', $order_id)
					->execute($db)
					->getRow();
		$order = static::fromArray($row);
		return $order;
	}

	public static function getProducts($db, $order_id) {
		$rows = static::select("op.*, p.key_pl AS slug")
					->from(static::class, "o")
					->leftJoin(OrderProduct::class, "op", "op.order_id = o.id")
					->leftJoin(Product::class, "p", "p.id = op.product_id")
					->where('o.id = :id')
					->setParameter(':id', $order_id)
					->execute($db)
					->getAll();
		$products = [];
		foreach ($rows as $row) {
			$item = OrderProduct::fromArray($row);
			$item->slug = $row["slug"];
			$products[] = $item;
		}
		return $products;
	}
}
?>