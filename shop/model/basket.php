<?php
namespace Model;
use \PDO;
use \ReflectionClass;
use \ReflectionProperty;

class DBModel {
	/*
		Parts of code are based on: 
			https://catchmetech.com/en/post/94/how-to-create-an-orm-framework-in-pure-php
	*/
	protected static $table_name;
	protected static $foreign_fields = [];
	protected static $primary_key;
	protected $aliases = [];

	protected $database;

	public function __construct($database) {
		$this->database = $database;
	}

	public function __get($key) {
		if (array_key_exists($key, $this->aliases)) {
			return $this->{$this->aliases[$key]};
		}
		return null;
	}

	public function __set($key, $value) {
		if (array_key_exists($key, $this->aliases)) {
			$this->{$this->aliases[$key]} = $value;
			return;
		}
		$this->{$key} = $value;
		return;
	}

	public function init() {} // virtual

	public function save() {
		$class = new ReflectionClass($this);
		$public_fields = $class->getProperties(ReflectionProperty::IS_PUBLIC);

		$key_value_pairs = [];
		$parameters = [];

		$primary = $this->getPrimaryKey();
		foreach ($public_fields as $property) {
			$property_name = $property->getName();
			if ($property_name == $primary) {
				continue;
			}

			$value = $this->{$property_name};
			if (is_null($value)) {
				continue;
			}

			$param_name = ":$property_name";

			$key_value_pairs[] = "`$property_name` = $param_name";
			$parameters[$param_name] = $value;
		}

		$set_clause = implode(', ', $key_value_pairs);
		
		$query = '';
		$table_name = $this->getTableName();

		if ($this->{$primary} > 0) {
			$query = "UPDATE `$table_name` SET $set_clause WHERE $primary = :$primary";
			$parameters[":$primary"] = $this->{$primary};
		} else {
			$query = "INSERT INTO `$table_name` SET $set_clause";
		}

		$statement = $this->database->prepare($query);
		$success = $statement->execute($parameters);
		
		return $success;
	}

	private function addJoinFields($addFields, $model) {
		$table = $model::getTableName();
		$primary = $model::getPrimaryKey();
		$fields = $model::getFields();

		foreach ($fields as $field) {
			$alias = $table."_".$field;
			$addFields[] = "$table.$field AS $alias";
		}

		return $addFields;
	}

	private function getWhere($where=[]) {
		$conditions = [];
		$params = []; 
		foreach ($where as $key => $value) {
			if (is_array($value)) {
				$condition = "$key IN (";
				$potential = [];
				foreach ($value as $n => $v) {
					$param = ":$key" . "__$n";
					$potential[] = $param;
					$params[$param] = $v;
				}
				$condition .= implode(", ", $potential) . ")";
			} else {
				$param = ":$key";
				$condition = "$key = $param";
				$params[$param] = $value;
			}
			$conditions[] = $condition;
		}

		$query = " WHERE " . implode(" AND ", $conditions);
		if (count($where) == 0) {
			$query = "";
		}
		$returned = [
			"query" => $query,
			"params" => $params
		];
		return $returned;
	}

	private function getQuery($where=[]) {
		$main_table = $this->getTableName();
		$fields = [];
		$fields = $this->addJoinFields($fields, $this);

		$used_models = [$this];

		$query_join = "";
		$foreign = $this->getForeignFields();
		foreach ($foreign as $join_element) {
			$model = $join_element["model"];
			$used_models[] = $model;

			$foreign_table = $model::getTableName();
			$foreign_primary = $model::getPrimaryKey();
			$fields = $this->addJoinFields($fields, $model);

			$method = $join_element["method"];
			$key = $join_element["key"];

			$join = "
			$method JOIN $foreign_table
				ON $key = $foreign_table.$foreign_primary
			";
			$query_join .= $join;
		}

		$fields = implode(", ", $fields);
		$params = [];

		$where = $this->getWhere($where);
		$query_where = $where["query"];
		$params = array_merge($params, $where["params"]);

		$query = "
		SELECT $fields 
		FROM $main_table
		$query_join
		$query_where
		";

		$returned = [
			"query" => $query,
			"params" => $params,
			"models" => $used_models
		];
		return $returned;
	}

	public function get($where=[]) {
		$q = $this->getQuery($where);
		$query = $q["query"];
		$params = $q["params"];
		$models = $q["models"];

		$statement = $this->database->prepare($query);
		$success = $statement->execute($params);
		if (!$success) {
			return False;
		}

		$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

		$result = [];
		foreach ($rows as $row) {
			foreach ($models as $model) {
				$result_row[$model::getTableName()] = $model::fromArray($this->database, $row);
			}
			$result[] = $result_row;
		}
		return $result;	
	}

	public static function fromArray($database, $array) {
		$model = get_called_class(); // it will be inherited
		$class = new ReflectionClass($model); // hence we need the child class

		$new_class = $class->newInstance($database);
		$public_fields = $class->getProperties(ReflectionProperty::IS_PUBLIC);

		$table = static::$table_name;
		foreach ($public_fields as $property) {
			$property_name = $property->getName();
			$array_key = $table . "_" . $property_name;
			$property_exists = isset($array[$array_key]);

			if ($property_exists) {
				$value = $array[$array_key];
				$property->setValue($new_class, $value);
			}
		}

		$new_class->init();

		return $new_class;
	}

	public static function getTableName() {
		return static::$table_name;
	}

	public static function getClassName() {
		return static::class;
	}

	public static function getPrimaryKey() {
		return static::$primary_key;
	}

	public static function getForeignFields() {
		return static::$foreign_fields;
	}

	public static function getFields() {
		$model = get_called_class();
		$class = new ReflectionClass($model);

		$instance = $class->newInstance(null);
		$public_fields = $class->getProperties(ReflectionProperty::IS_PUBLIC);

		$table_name = $instance->getTableName();
		$primary = $instance->getPrimaryKey();

		$fields = [];
		foreach ($public_fields as $property) {
			$property_name = $property->getName();
			$fields[] = $property_name;
		}

		return $fields;
	}
}

class QueryBuilder {
	public $model;
	public $join = [];

	public function generate() {
		
	}
	
}

class ProductConnection extends DBModel {
	protected static $table_name = "product_connection";
	protected static $foreign_fields = [
		[
			"key" => "product_connection.pid", 
			"model" => ProductDB::class,
			"method" => "inner"
		], [
			"key" => "product_connection.cid",
			"model" => CategoryDB::class,
			"method" => "inner"
		]
	];
	protected static $primary_key = "id";

	public $sort;
	public $id;
	public $pid;
	public $cid;
}

class ProductDB extends DBModel {
	protected static $table_name = "product";
	protected static $primary_key = "id";
	protected static $foreign_fields = [];

	protected $aliases = [
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
}

class CategoryDB extends DBModel {
	protected static $table_name = "category";
	protected static $primary_key = "id";
	protected static $foreign_fields = [];

	public $id;
	public $key_pl;
	public $sub;
	public $level;
	public $sub_count;
	public $name_pl;
	public $sort;
	public $visible_pl;
	public $group;
	public $cdate;
}

class Product {
	public const TABLE = "product";

	public $id;
	public $slug;
	public $symbol;
	public $name;
	public $description;
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
	public $price_netto = null;
	public $price_brutto = null;
	public $added_time = null;

	public function test() {
		$this->id = 13;
		$this->slug = "kafelek-lazienkowy";
		$this->symbol = "K4F3LK1";
		$this->name = "Kafelek łazienkowy";
		$this->description = "Poczuj się jak w łazience.";
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
		$price_netto = $this->price_netto ?? $this->getPrice("netto");
		$price_brutto = $this->price_brutto ?? $this->getPrice("brutto");
		$added_time = $this->added_time ?? strtotime("now");

		$item = [
			"id" => $this->id,
			"slug" => $this->slug,
			"name" => $this->name,
			"price_netto" => $price_netto,
			"price_brutto" => $price_brutto,
			"photo" => $this->photos["small"],
			"symbol" => $this->symbol,
			"added" => $added_time
		];

		return $item;
	}

	public static function fromBasketItem($item) {
		$product = new Product();

		$product->id = $item["id"];
		$product->slug = $item["slug"];
		$product->name = $item["name"];
		$product->price_netto = $item["price_netto"];
		$product->price_brutto = $item["price_brutto"];
		$product->photos["small"] = $item["photo"];
		$product->symbol = $item["symbol"];
		$product->added_time = $item["added"];

		return $product;
	}

	public static function fromDatabase($item) {
		$product = new Product();

		$product->id = $item["id"];
		$product->slug = $item["slug"];
		$product->name = $item["name"];
		$product->photos["small"] = $item["photo_small"];
		$product->photos["medium"] = $item["photo_medium"];
		$product->photos["big"] = $item["photo_big"];
		$product->symbol = $item["symbol"];
		$product->description = $item["description"];
		$product->stock = $item["stock"];
		$product->vat = $item["vat"];
		$product->price_normal["netto"] = $item["price_normal_netto"];
		$product->price_normal["brutto"] = $item["price_normal_brutto"];
		$product->price_discounted["netto"] = $item["price_discounted_netto"];
		$product->price_discounted["brutto"] = $item["price_discounted_brutto"];
		$product->purchase_count = $item["purchase_count"];
		$product->view_count = $item["view_count"];
		$product->view_date = $item["view_date"];
		$product->creation_date = $item["creation_date"];

		return $product;
	}

	public static function getColumns() {
		return "
			product.id, product.key_pl AS slug, product.symbol AS symbol, product.name_pl AS name, product.desc_pl AS description, product.stock, product.vat, product.priceAn AS price_normal_netto, product.priceBn AS price_discounted_netto, product.priceAg AS price_normal_brutto, product.priceBg AS price_discounted_brutto, product.fotos AS photo_small, product.fotom AS photo_medium, product.fotob AS photo_big, product.purchase_count, product.view_count, product.view_date, product.cdate AS creation_date
		";
	}

	public static function getProducts($database) {
		$params = [];
		$table = Product::TABLE;
		$columns = Product::getColumns();
		$query = "
			SELECT $columns FROM $table AS product;
		";

		$statement = $database->prepare($query);
		$statement->execute($params);
		$result = $statement->fetchAll(PDO::FETCH_ASSOC);

		$products = [];
		foreach ($result as $item) {
			$products[] = Product::fromDatabase($item);
		}
		return $products;
	}
}

class Basket {
	private const SHIPMENT = 10;
	private $basket;
	private $data;

	public $netto;
	public $brutto;
	public $shipment;
	public $products;
	public $total;

	public function __construct($data) {
		$this->data = $data;
		$this->retrieve();
		if (is_null($this->basket)) {
			$this->update();
			$this->retrieve();
		}

		$this->netto = floatval($this->basket["netto"]);
		$this->brutto = floatval($this->basket["brutto"]);
		$this->shipment = floatval($this->basket["shipment"]);
		$this->products = $this->basket["products"];

		$this->total = $this->brutto + $this->shipment;
	}

	private function retrieve() {
		$this->basket = $this->data->session->basket;
	}

	private function push() {
		$this->update(
			$this->netto, $this->brutto, $this->shipment, $this->products
		);
	}

	public function update($netto=0, $brutto=0, $shipment=0, $products=[]) {
		$_SESSION["basket"] = [
			"netto" => $netto,
			"brutto" => $brutto,
			"shipment" => $shipment,
			"products" => $products
		];
		$this->netto = $netto;
		$this->brutto = $brutto;
		$this->shipment = $shipment;
		$this->products = $products;
	}

	public function isEmpty() {
		return count($this->products) == 0;
	}

	public function addProduct($product) {
		$item = $product->asBasketItem();
		if ($this->isEmpty()) {
			$this->shipment = Basket::SHIPMENT;
		}
		$this->netto += $item["price_netto"];
		$this->brutto += $item["price_brutto"];
		$this->products[] = $item;

		$this->push();
	}

	public function removeProduct($product) {
		$slug = $product->slug;
		foreach ($this->products as $i => $product) {
			if ($product->slug == $slug) {
				$this->netto -= $product->price_netto;
				$this->brutto -= $product->price_brutto;
				unset($this->products[$i]);
				break;
			}
		}

		if ($this->isEmpty()) {
			$this->shipment = 0;
		}
		$this->push();
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

	public function viewProduct($product) {
		$now = strtotime("now");
		$element = [
			"id" => $product->id,
			"slug" => $product->slug,
			"category" => $product->category,
			"seen" => $now
		];

		$this->addElement($element);
	}

	private function addElement($element) {
		$_SESSION["history"]["viewed_products"][] = $element;
	}

	private function setElements($elements) {
		$_SESSION["history"]["viewed_products"] = $elements;
	}

	private function setClearDate($date) {
		$_SESSION["history"]["clear_date"] = $date;
	}

	private function reset() {
		$_SESSION["history"] = [];
		$_SESSION["history"]["viewed_products"] = [];
		$_SESSION["history"]["clear_date"] = date('Y-m-d');
	}

	private function update() {
		$history = $this->data->session->history;
		if (is_null($history)) {
			$this->reset();
			$this->update();
			return;
		}

		$this->viewed_products = $history["viewed_products"]; 
		$this->clear_date = $history["clear_date"];
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

		$this->setElements($viewed);
		$this->setClearDate($today);

		$this->update();
	}
}
?>
