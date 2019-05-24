<?php
include_once __DIR__ . "/../env/config.php";

include_once ROOT_PATH . "/config/database.php";
include_once ROOT_PATH . "/config/functions.php";
include_once ROOT_PATH . "/model/basket.php";

use Model\Basket;
use Model\Product;
use Model\History;

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
	public $action_method = "get";
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
		$this->handleActions();

		$this->response->addTemplate($this->template_path->head, $this->metadata->getMetadata());

		if ($this->snackbar->getMessage()) {
			$this->response->addTemplate($this->template_path->header_msg, [
				"message" => $this->snackbar->getMessage(),
				"code" => $this->snackbar->getCode(),
			]);
		}

		$this->response->addTemplate($this->template_path->header, []);
		return True;
	}

	public function content() {} // virtual
	public function init() {}

	public function run() {
		$this->_init();
		$this->init();
		$this->content();
		$this->render();
	}

	public function render() {
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


class ShopBuilder extends WebBuilder {
	protected $basket;
	protected $history;

	public function __construct() {
		parent::__construct();
		$this->basket = new Basket($this->data);
		$this->history = new History($this->data);
	}
}
?>