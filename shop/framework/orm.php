<?php
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

class QueryBuilder {
	private $fields = "";
	private $table = "";
	private $where = "";
	private $order = "";
	private $parameters = [];
	private $joins = [];

	private $statement;
	private $database;

	public function __construct() {}
	public function select($fields) {
		if (!is_array($fields)) {
			$fields = [$fields];
		}

		$this->fields = implode(", ", $fields);
		return $this;
	}

	public function from($model, $alias="") {
		$table_name = $this->getModelTable($model);
		$this->table = "`$table_name` AS $alias";
		return $this;
	}

	public function where($condition) {
		if ($condition == "") {
			$this->where = "";
		} else {
			$this->where = "WHERE " . $condition;
		}
		return $this;
	}

	public function orderBy($column, $direction="asc") {
		$directions = ["asc", "desc", "ascending", "descending", ""];
		$valid = in_array(strtolower($direction), $directions);
		if (!$valid) {
			throw new Exception("Invalid direction - $direction");
		}

		$this->order = "";
		if ($column) {
			$this->order = "ORDER BY $column $direction";
		}
		return $this;
	}

	public function setParameters($params) {
		$this->parameters = $params;
		return $this;
	}

	public function addParameters($params) {
		foreach ($params as $param => $value) {
			$this->parameters[$param] = $value;
		}
		return $this;
	}

	public function setParameter($param, $value) {
		$this->parameters[$param] = $value;
		return $this;
	}

	public function leftJoin($model, $alias, $on) {
		return $this->join("left", $model, $alias, $on);
	}
	public function rightJoin($model, $alias, $on) {
		return $this->join("right", $model, $alias, $on);
	}
	public function innerJoin($model, $alias, $on) {
		return $this->join("inner", $model, $alias, $on);
	}

	public function execute($database) {
		$this->database = $database;
		$query = $this->getQuery();
		$this->statement = $database->prepare($query);
		$this->statement->execute($this->parameters);
		return $this;
	}

	public function getAll() {
		return $this->statement->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getRow() {
		return $this->statement->fetch(PDO::FETCH_ASSOC);
	}

	public function getQuery() {
		$q = "SELECT $this->fields\n";
		$q .= "FROM $this->table\n";

		$joins = implode("\n", $this->joins);
		$q .= "$joins\n";
		$q .= "$this->where\n";
		$q .= "$this->order";

		return $q;
	}

	private function join($method, $model, $alias, $on) {
		$table_name = $this->getModelTable($model);
		$query = "$method JOIN $table_name AS $alias\nON $on";
		$this->joins[] = $query;
		return $this;
	}
	private function getModelTable($model) {
		return $model::getTableName();
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

	public function __construct() {}

	public function init() {} // virtual

	public function save($database) {
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

		$statement = $database->prepare($query);
		$success = $statement->execute($parameters);

		if ($primary_id <= 0) {
			$this->setValue($primary, $database->lastInsertId());
		}
		
		return $success;
	}

	public static function select($fields) {
		$query_builder = new QueryBuilder();
		return $query_builder->select($fields);
	}

	public static function fromArray($array, $prefix="") {
		$model = get_called_class(); // it will be inherited
		$class = new ReflectionClass($model); // hence we need the child class

		$new_class = $class->newInstance();
		$public_fields = $class->getProperties(ReflectionProperty::IS_PUBLIC);

		$table = static::$table_name;
		$alias_fields = [];

		foreach ($public_fields as $property) {
			$property_name = $property->getName();
			$array_key = $prefix . $property_name;
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
?>