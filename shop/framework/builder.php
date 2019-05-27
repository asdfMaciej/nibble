<?php
include_once __DIR__ . "/../env/config.php";
include_once ROOT_PATH . "/framework/functions.php";

class DBClass {
	private $connection = null;

	private $host = DB_HOST;
	private $username = DB_USERNAME;
	private $password = DB_PASSWORD;
	private $database = DB_DATABASE;

	public function getConnection() {
		if (is_null($this->connection)) {
			try {
				$call = "mysql:host=" . $this->host . ";dbname=" . $this->database;
				$this->connection = new PDO($call, $this->username, $this->password);
				$this->connection->exec("set names utf8");
			} catch(PDOException $exception) {
				$this->connection = null;
				echo "Error: " . $exception->getMessage();
			}
		}

		return $this->connection;
	}
}

interface ResponseBuilderInterface {
	public function generate();
}

interface TemplateInterface {
	public function generate($data);
}

class ResponseBuilder implements ResponseBuilderInterface {
	private $response = [
		"data" => [], 
		"status" => [
			"code" => 200, 
			"message" => ""
		]
	];
	private $error_msg = "";
	private $response_code = 200;
	

	public function r_ok() {$this->response_code = 200;}
	public function r_created() {$this->response_code = 201;}
	public function r_bad_request() {$this->response_code = 400;}
	public function r_unauthorized() {$this->response_code = 401;}
	public function r_forbidden() {$this->response_code = 403;}
	public function r_not_found() {$this->response_code = 404;}

	public function setError($error) {
		$this->error_msg = $error;
		$this->response["status"]["message"] = $this->error_msg;
	}

	public function generateAndSet($code, $message, $data=[]) {
		$this->error_msg = $message;
		$this->response_code = $code;
		return $this->generate($data);
	}

	public function generate($data=[]) {
		$this->response["status"]["message"] = $this->error_msg;
		$this->response["status"]["code"] = $this->response_code;
		$this->response["data"] = $data;

		header("Access-Control-Allow-Origin: *");
		header("Content-Type: application/json");
		http_response_code($this->response_code);

		return json($this->response);
	}
	
}

class DataFromArray {
	private $array;
	public function __construct(&$array) {
		$this->array = &$array;
	}

	public function &__get($key) {
		$value = $this->array[$key] ?? null;
		return $value;
	}

	public function __set($key, $value) {
		$this->array[$key] = $value;
	}
}

class DataFromPath {
	private $path_levels;

	public function __construct() {
		$uri = explode('?', $_SERVER['REQUEST_URI'], 2); // /maindir/app?a=123&b=23
		$path_absolute = $uri[0]; // /maindir/app
		$prefix_length = strlen(PATH_PREFIX); // /maindir
		$path_relative = substr($path_absolute, $prefix_length); // /app

		$path_levels = explode("/", $path_relative); // ['', app]
		array_shift($path_levels); // the first item is always empty

		$this->path_levels = $path_levels;
	}

	public function __get($key) {
		// /app/a/b/c/d  /key/value/  e/f/g

		$value = "";
		$found_key = False;
		foreach ($this->path_levels as $level) {
			if ($found_key) { // occurs on the iteration after key find
				$value = $level;
				break;
			}

			if ($level === $key) {
				$found_key = True;
			}
		}

		$value = urldecode($value);

		return $value;
	}
}

class DataHandler {
	public $get, $post, $session;
	public function __construct() {
		$this->get = new DataFromArray($_GET);
		$this->post = new DataFromArray($_POST);
		$this->session = new DataFromArray($_SESSION);
		$this->path = new DataFromPath($_SESSION);
	}
}

class TemplatePaths {
	public $head = "template/head.php";
	public $header_msg = "template/header_message.php";
	public $header = "template/header.php";
	public $foot = "template/foot.php";
	public $footer = "template/footer.php";
}

class PageMetadata {
	private $stylesheet_prefix = "/style/";
	private $script_prefix = "/javascript/";

	protected $title = "";
	protected $stylesheets = [];
	protected $scripts = [];

	public function __construct() {}

	protected function addPostfix(&$name, $postfix) {
		$postfix_length = strlen($postfix);
		$postfix_index = 0 - $postfix_length;

		$name_postfix = substr($name, $postfix_index);
		if ($name_postfix !== $postfix) {
			$name .= $postfix;
		}
		return $name;
	}

	public function addStylesheet($name) {
		$path = $this->stylesheet_prefix;
		$name = $path . $this->addPostfix($name, ".css");
		$this->stylesheets[] = $name;
	}

	public function addScript($name) {
		$path = $this->script_prefix;
		$name = $path . $this->addPostfix($name, ".js");
		$this->scripts[] = $name;
	}

	public function setTitle($title) {
		$this->title = $title;
	}

	public function getStylesheets() { return $this->stylesheets; }
	public function getScripts() { return $this->scripts; }
	public function getTitle() { return $this->title; }

	public function getMetadata() {
		$metadata = [
			"stylesheets" => $this->getStylesheets(),
			"scripts" => $this->getScripts(),
			"title" => $this->getTitle()
		];
		return $metadata; 
	}
}

class WebBuilderConfig {
	public $autorun = True;
	public $action_key = "action";
	public $action_method = "post";
}

class PageSnackbar {
	private $message = "";
	private $code = 200;

	public function setMessage($msg) {
		$this->message = $msg;
	}
	public function setCode($code) {
		$this->code = $code;
	}

	public function getMessage() { return $this->message; }
	public function getCode() { return $this->code; }
}


class WebBuilder {
	protected $config;
	protected $metadata;
	protected $template_path;
	protected $database;
	protected $database_class;
	protected $response;
	protected $data;
	protected $snackbar;

	protected $token = "";
	protected $get_method = "get";

	protected $token_get = "token";
	protected $call_name;

	protected $functions_map = [];

	public function __construct() {
		$this->database_class = new DBClass();
		$this->database = $this->database_class->getConnection();
		$this->response = new TemplateBuilder();
		$this->template_path = new TemplatePaths();
		$this->metadata = new PageMetadata();
		$this->config = new WebBuilderConfig();
		$this->data = new DataHandler();
		$this->snackbar = new PageSnackbar();
	}

	public function __destruct() {
		if ($this->config->autorun) {
			$this->run();
		}
	}

	public function _init() {
		$this->metadata->addStylesheet("style.css");

		$this->response->addTemplate($this->template_path->head, $this->metadata->getMetadata());

		$this->response->addTemplate($this->template_path->header, []);
		return True;
	}

	public function content() {} // virtual
	public function init() {}

	public function run() {
		$this->_init();
		$this->init();
		$this->handleActions();
		$this->content();
		$this->render();
	}

	public function render() {
		if ($this->snackbar->getMessage()) {
			$this->response->addTemplate($this->template_path->header_msg, [
				"message" => $this->snackbar->getMessage(),
				"code" => $this->snackbar->getCode(),
			]);
		}

		$this->response->addTemplate($this->template_path->footer);
		$this->response->addTemplate($this->template_path->foot);
		$this->response->generate();
	}

	public function addAction($action, $function_name) {
		$this->functions_map[$action] = $function_name;
	}

	public function handleActions() {
		$action_method = strtolower($this->config->action_method);
		$action_key = $this->config->action_key;

		if ($action_method == "get") {
			$value_array = $_GET;
		} elseif ($action_method == "post") {
			$value_array = $_POST;
		} else {
			throw new Exception("Invalid action retrieve method [post/get]: ".$action_method);
		}

		$action = $value_array[$action_key] ?? "";
		if (!array_key_exists($action, $this->functions_map)) {
			return False;
		}

		$function_name = $this->functions_map[$action];
		$call = [$this, $function_name]; // class object, function name
		$response = call_user_func($call, $this); // $this is passed as a param
		if (is_array($response)) {
			$this->snackbar->setCode($response[0]);
			$this->snackbar->setMessage($response[1]);
		}
		return True;
	}
}

class TemplateBuilder implements ResponseBuilderInterface {
	protected $templates = [];
	protected $templates_data = [];

	protected $response_code = 200;

	public function __construct() {}

	public function addTemplate($filename, $data=[]) {
		if (is_string($filename)) {
			$tmp = new Template();
			if (!$tmp->setTemplateFile($filename)) {
				return False;
			}
		} elseif ($filename instanceof TemplateInterface) {
			$tmp = $filename;
		} else {
			throw new Exception("Template isn't neither a filename nor implements TemplateInterface.");
		}

		$this->templates[] = $tmp;
		$this->templates_data[] = $data;
	}

	public function setResponseCode($code) {
		$this->response_code = $code;
	}

	public function generate() {
		http_response_code($this->response_code);

		foreach ($this->templates as $n => $template) {
			$template->generate($this->templates_data[$n]);
		}
	}

}



class Template implements TemplateInterface {
	protected $template_dir = "/templates/";
	protected $template_path = "";
	protected $nest_extract = [];

	public function __construct() {

	}

	public function setTemplateFile($filename) {
		$dir = ROOT_PATH . $this->template_dir;
		$path = $dir . $filename;
		if (file_exists($path) && is_readable($path)) {
			$this->template_path = $path;
			return True;
		} else {
			throw new Exception("Cannot read template file: ".$filename.", path: ".$path);
		}
	}

	protected function showVariable($name) {

	}
	public function generate($data) {
		if ($this->template_path === "") {
			return;
		}
		$content = file_get_contents($this->template_path);
		$content = "?>" . $content;// . "<?php";
		$content = preg_replace('~{{ *([\w->\[\]"\']+) *}}~', '<?php echo($$1); ?>', $content);

		extract($this->nest_extract);
		$this->nest_extract = $data;
		extract($this->nest_extract);

		eval($content);
	}

	protected function nest($filename, $data) { // input there should be correct
		$temp = new Template();
		$temp->setTemplateFile($filename);
		$temp->generate($data);
	}
}

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