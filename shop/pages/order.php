<?php
namespace Web\Pages;
use \Model\Order;
use \Model\OrderProduct;

class Index extends \ShopBuilder {
	private $orders;

	protected function init() {
		$this->metadata->setTitle("Sklep - koszyk");
		$this->addAction("new_order", "onNewOrder");
	}

	protected function content() {
		$order_id = $this->data->path->order;
		if ($order_id === "") {
			$this->contentList();
		} else {
			$this->contentOrder($order_id);
		}
	}

	protected function contentList() {
		$this->orders = Order::getOrders($this->database);
		$this->response->addTemplate("orders_list.php", [
			"orders" => $this->orders
		]);
	}

	protected function contentOrder($order_id) {
		$order = Order::getOrder($this->database, $order_id);
		$products = Order::getProducts($this->database, $order_id);
		$this->response->addTemplate("order.php", [
			"order" => $order,
			"products" => $products
		]);
	}

	protected function onNewOrder() {
		if ($this->basket->empty) {
			$this->snackbar->setMessage("Koszyk nie może być pusty!");
			$this->snackbar->setCode(400);
			return;
		}

		$new_order = new Order();
		$new_order->netto = $this->basket->netto;
		$new_order->brutto = $this->basket->brutto;
		$new_order->total = $this->basket->total;
		$new_order->address = "dworcowa 11";
		$new_order->ip = "8.8.8.8";
		$new_order->customer_id = 1;

		$new_order->save($this->database);
		$order_id = $new_order->id;

		$order_product = new OrderProduct();
		foreach ($this->basket->products as $product) {
			$order_product->product_id = $product['id'];
			$order_product->netto = $product['price_netto'];
			$order_product->brutto = $product['price_brutto'];
			$order_product->vat = $product['vat'];
			$order_product->name = $product['name'];
			$order_product->quantity = 1;
			$order_product->order_id = $order_id;

			$order_product->save($this->database);
			$order_product->id = 0;
		}
		
		$this->basket->clear();
		$this->snackbar->setMessage("Dodano zamówienie - ID: $order_id");
		$this->snackbar->setCode(200);
	}
}

new Index();
?>