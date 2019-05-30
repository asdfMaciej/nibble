<?php
namespace Web\Pages;
use \Model\Product;
use \Model\OrderProduct;
use \Model\Category;


class Index extends \ShopBuilder {
	protected function init() {
		$this->metadata->setTitle("Sklep - polecone przedmioty");
	}

	protected function onAddBasket() {
		$this->basket->addProduct($this->product);
		$this->snackbar->setMessage("Dodano do koszyka");
		$this->snackbar->setCode(200);
	}

	/*
	1. Wyświetlanie ostatnio oglądanych przez danego użytkownika produktów, które będą zapamiętywane przez 30 dni w przeglądarce

	2. Wyświetlanie najczęściej kupowanych produktów na podstawie analizy bazy danych pod kątem zakupów zrealizowanych przez wszystkich klientów sklepu

	3. Wyświetlanie najczęście oglądanych produktów na podstawie faktycznej liczby wyświetleń produktów w systemie przez wszystkich jego użytkowników

	4. Wyświetlanie produktów z tych kategorii, w któych dany uzytkownik był, ale ich nie obejrzał (tzn oglądał tylko inne produkty z tej kategorii lub nie obejrzał żadnego)

	5. Wyświetlanie produktów, które najczęściej są kupowane razem na podstawie implementacji algorytmu Apriori 
	*/
	protected function content() {
		$bought = Product::select("p.*, COUNT(op.id) AS purchase_count")
			->from(OrderProduct::class, "op")
			->leftJoin(Product::class, "p", "p.id = op.product_id")
			->groupBy("op.product_id")
			->orderBy("purchase_count", "desc")
			->execute($this->database)
			->getAll();

		$top_bought = [];
		$limit = 3;
		foreach ($bought as $b) {
			$top_bought[] = Product::fromArray($b);
			$limit -= 1;
			if ($limit == 0) {break;}
		}

		$products = Category::getProducts($this->database);
		$products_id = [];
		$products_cid = [];
		foreach ($products as $p) {
			$products_id[$p->id] = $p;
			if (!array_key_exists($p->category_id, $products_cid)) {
				$products_cid[$p->category_id] = [];
			}
			$products_cid[$p->category_id][] = $p;
		}
		//var_dump($products_id);
		//var_dump($products_cid);

		$views = [];
		foreach ($this->history->viewed_products as $p) {
			if (!array_key_exists($p["id"], $views)) {
				$views[$p["id"]] = 0;
			}
			$views[$p["id"]] += 1;
		}

		arsort($views);
		//var_dump($views);
		$top_viewed = [];
		$limit = 3;
		foreach ($views as $pid => $view_c) {
			$p = Product::getSingleItem($this->database, ["id" => $pid]);
			$p->view_count = $view_c;
			$top_viewed[] = $p;
			$limit -= 1;
			if ($limit == 0) {break;}
		}

		$last_viewed = [];
		$last3 = array_reverse(array_slice($this->history->viewed_products, -3, 3));
		foreach ($last3 as $p) {
			$d = Product::getSingleItem($this->database, ["id" => $p["id"]]);
			$d->view_date = $p["seen_on"];
			$last_viewed[] = $d;
		}

		

		$existing_categories = []; // user was in but didnt view
		foreach ($this->history->viewed_categories as $c) {
			$cid = $c["id"];
			if (!array_key_exists($cid, $existing_categories)) {
				$existing_categories[$cid] = 666;
			}
		}
		foreach ($this->history->viewed_products as $p) {
			$pid = $p["id"];
			$cid = $products_id[$pid]->category_id;
			unset($existing_categories[$cid]);
		}
		$pfcuwdv = []; //productsforcategoriesuserwasindidntview
		foreach ($existing_categories as $cid => $dupa) {
			$pfcuwdv = array_merge($pfcuwdv, $products_cid[$cid]);
		}
		$categories = [];
		foreach ($this->history->viewed_categories as $c) {
			if (!in_array($c["id"], $categories)) {
				$categories[] = $c["id"];
			}
		}
		$product_pairs = [];
		$order_p = [];
		foreach (OrderProduct::getItems($this->database) as $op) {
			if (!array_key_exists($op->order_id, $order_p)) {
				$order_p[$op->order_id] = [];
			}
			if (!in_array($op->product_id, $order_p[$op->order_id])) {
				$order_p[$op->order_id][] = $op->product_id; 
			}
		}

		foreach ($order_p as $order_id => $products) {
			foreach ($products as $pid) {
				if (!array_key_exists($pid, $product_pairs)) {
					$product_pairs[$pid] = [];
				}
				foreach ($order_p[$order_id] as $pid2) {
					if ($pid2 == $pid) {continue;}
					if (!array_key_exists($pid2, $product_pairs[$pid])) {
						$product_pairs[$pid][$pid2] = 0;
					}
					$product_pairs[$pid][$pid2] += 1;
				}
			}
		}

		function hash($p1, $p2) {return "$p1||$p2";}
		function unhash($hash) {return explode("||", $hash);}

		$pairs = [];
		foreach ($product_pairs as $pid1 => $pp) {
			foreach ($pp as $pid2 => $pair_n) {
				if (array_key_exists(hash($pid2, $pid1), $pairs)) {
					continue;
				}
				$pairs[hash($pid1, $pid2)] = $pair_n;
			}
		}

		arsort($pairs);
		$pairspairs = [];
		foreach ($pairs as $hash => $n) {
			$xd = unhash($hash);
			$p1 = Product::getSingleItem($this->database, ["id" => $xd[0]]);
			$p2 = Product::getSingleItem($this->database, ["id" => $xd[1]]);
			$pairspairs[] = [$p1, $p2, $n];
		}

		$this->response->addTemplate("rec1.php", [
			"products" => $top_viewed
		]);
		$this->response->addTemplate("rec2.php", [
			"products" => $top_bought
		]);
		$this->response->addTemplate("rec3.php", [
			"products" => $last_viewed
		]);
		$this->response->addTemplate("rec4.php", [
			"products" => $pfcuwdv
		]);
		$this->response->addTemplate("rec5.php", [
			"pairs" => $pairspairs
		]);

		//var_dump($top3pairs);

		
		//var_dump($order_p);
		//var_dump($existing_categories);
		//var_dump($categories);
		///var_dump(Category::getProducts($this->database));
		//var_dump($top_bought);
		//var_dump($this->history->viewed_products);


		$products_map = "";
	}
}

new Index();
?>