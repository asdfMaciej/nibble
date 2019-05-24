<?php
namespace Model;
use \PDO;
use \ReflectionClass;
use \ReflectionProperty;

class Model {
	protected static $aliases = [];
	protected $computed = [];

	public function __construct() {}

	public function &__get($key) {
		if (array_key_exists($key, static::$aliases)) {
			$key = static::$aliases[$key];
		}

		if (array_key_exists($key, $this->computed)) {
			$value = $this->computed[$key]();
			return $value;
		}

		$value = &$this->{$key} ?? null;
		return $value;
	}

	public function __set($key, $value) {
		if (array_key_exists($key, static::$aliases)) {
			$this->{static::$aliases[$key]} = $value;
			return;
		}
		$this->{$key} = $value;
		return;
	}

	public function init() {} // virtual
	public function save() {} // virtual

	public static function getFields($model=null) {
		if (is_null($model)) {
			$model = get_called_class();
		}

		$class = new ReflectionClass($model);
		$public_fields = $class->getProperties(ReflectionProperty::IS_PUBLIC);

		$props = [];
		foreach ($public_fields as $property) {
			$property_name = $property->getName();
			$props[] = $property_name;
		}

		return $props; 
	}

	public function getValue($key) {
		return $this->{$key};
	}

	public function setValue($key, $value) {
		$this->{$key} = $value;
	}

}

class SessionModel extends Model {
	private $class_name;
	private $session_group = "SessionModels";

	public function __construct() {
		parent::__construct();

		$this->class_name = static::class;
		$this->initSession();

		$this->init();
	}

	public function __set($key, $value) {
		if (array_key_exists($key, static::$aliases)) {
			$key = static::$aliases[$key];
		}
		$this->setSessionField($key, $value);
	}

	public function &__get($key) {
		if (array_key_exists($key, static::$aliases)) {
			$key = static::$aliases[$key];
		}

		if (array_key_exists($key, $this->computed)) {
			$value = $this->computed[$key]();
			return $value;
		}
		return $this->getSessionField($key);
	}

	protected function initSession() {
		if (!isset($_SESSION[$this->session_group])) {
			$_SESSION[$this->session_group] = [];
		}

		if (!isset($_SESSION[$this->session_group][$this->class_name])) {
			$_SESSION[$this->session_group][$this->class_name] = $this->fields;
		}
	}

	protected function &getSessionField($field) {
		return $_SESSION[$this->session_group][$this->class_name][$field];
	}

	protected function setSessionField($field, $value) {
		$_SESSION[$this->session_group][$this->class_name][$field] = $value;
	}
}

class DBModel extends Model {
	/*
		Parts of code are based on: 
			https://catchmetech.com/en/post/94/how-to-create-an-orm-framework-in-pure-php
	*/
	protected static $table_name;
	protected static $foreign_fields = [];
	protected static $primary_key;
	protected static $aliases = [];

	protected $database;

	public function __construct($database) {
		$this->database = $database;
	}


	public function init() {} // virtual

	public function save() {
		$key_value_pairs = [];
		$parameters = [];
		$primary = $this->getPrimaryKey();

		$props = static::getFields();
		foreach ($props as $property_name) {
			if ($property_name == $primary) {
				continue;
			}

			$value = $this->getValue($property_name);
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

		$primary_id = $this->getValue($primary);
		if ($primary_id > 0) {
			$query = "UPDATE `$table_name` SET $set_clause WHERE $primary = :$primary";
			$parameters[":$primary"] = $primary_id;
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

	private function getWhere($where, $used_models) {
		$conditions = [];
		$params = [];
		foreach ($where as $key => $value) {
			/*
				if only column provided: it finds the
					first table with such column or alias
			*/
			if (strpos($key, '.') === false) {
				foreach ($used_models as $model) { // calling body is 1st
					$fields = $model::getFields();
					$key_check = $key . "";
					if (array_key_exists($key, $model::$aliases)) {
						$key_check = $model::$aliases[$key];
					}

					if (in_array($key_check, $fields)) {
						$key = $model::getTableName() . "." . $key_check;
						break;
					}
				}
			}

			if (strpos($key, '.') === false) {
				throw new Exception("Unable to find key: $key");
			}

			/*
				in case table.alias was provided at start,
				it finds the matching column
			*/
			$parts = explode('.', $key);
			$key_table = strtolower($parts[0]);
			$key_column = $parts[1];
			foreach ($used_models as $model) {
				$table_name = strtolower($model::getTableName());
				if ($table_name != $key_table) {
					continue;
				}

				if (array_key_exists($key_column, $model::$aliases)) {
					$key_column = $model::$aliases[$key_column];
					break;
				}
			}

			$key = "$key_table.$key_column";

			if (is_array($value)) {
				$condition = "$key IN (";
				$potential = [];
				foreach ($value as $n => $v) {
					$param = ":$key" . "__$n";
					$param = str_replace('.', '_', $param);
					$potential[] = $param;
					$params[$param] = $v;
				}
				$condition .= implode(", ", $potential) . ")";
			} else {
				$param = ":$key";
				$param = str_replace('.', '_', $param);
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

		$where = $this->getWhere($where, $used_models);
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
		var_dump($query);
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
		$alias_fields = [];
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

}

class ProductConnection extends DBModel {
	protected static $table_name = "product_connection";
	protected static $foreign_fields = [
		[
			"key" => "product_connection.pid", 
			"model" => Product::class,
			"method" => "inner"
		], [
			"key" => "product_connection.cid",
			"model" => Category::class,
			"method" => "inner"
		]
	];
	protected static $primary_key = "id";

	public $sort;
	public $id;
	public $pid;
	public $cid;
}

class Product extends DBModel {
	protected static $table_name = "product";
	protected static $primary_key = "id";
	protected static $foreign_fields = [];

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
			"added_time" => $added_time
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

class Category extends DBModel {
	protected static $table_name = "category";
	protected static $primary_key = "id";
	protected static $foreign_fields = [];
	protected static $aliases = [
		"slug" => "key_pl",
		"name" => "name_pl",
		"desc" => "desc_pl",
		"creation_date" => "cdate",
		"parent_id" => "sub"
	];

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

	public function asHistoryItem() {
		$item = [
			"name" => $this->name,
			"id" => $this->id,
			"parent_id" => $this->parent_id,
			"level" => $this->level
		];

		return $item;
	}
}

class Basket extends SessionModel {
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
}

class History extends SessionModel {
	protected $fields = [
		"clear_date" => "1970-01-01",
		"viewed_products" => [],
		"viewed_categories" => []
	];

	public function init() {
		$today = date('Y-m-d');
		if ($today !== $this->clear_date) {
			$this->clearHistory();
		}
	}

	public function addProduct($product) {
		$element = $product->asHistoryItem();
		$this->viewed_products[] = $element;
	}

	public function addCategory($category) {
		$element = $category->asHistoryItem();
		$this->viewed_categories[] = $element;
	}

	private function clearHistory() {
		$month_ago = strtotime("-1 month");
		$today = date('Y-m-d');

		$categories = ["viewed_products", "viewed_categories"];
		foreach ($categories as $category_name) {
			$category = $this->{$category_name};

			$old_items_count = 0;
			foreach ($category as $product) { // binary search would be faster
				$seen = intval($product["seen"]);
				if ($seen <= $month_ago) {
					$old_items_count += 1;
				} else {
					break;
				}
				$viewed = array_slice($category, $old_items_count);
				$this->{$category_name} = $viewed;
			}
		}

		$this->clear_date = $today;
	}
}
?>
