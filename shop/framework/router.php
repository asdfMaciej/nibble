<?php
class Router {
	/* source modified based on
		https://github.com/moagrius/RegexRouter/ */
	private $pages_path = ROOT_PATH . "/pages/";
	private $routes = array();

	public $page404 = "404.php";

	public function route($pattern, $page_name) {
		$pattern = '/^'. preg_quote(PATH_PREFIX, '/') . '\/' . $pattern . '$/';
		$this->routes[$pattern] = $page_name;
	}

	public function execute() {
		$uri = explode('?', $_SERVER['REQUEST_URI'], 2);
		$uri = $uri[0];

		foreach ($this->routes as $pattern => $page_name) {
			if (preg_match($pattern, $uri, $params) === 1) {
				array_shift($params);
				return $this->showPage($page_name, $params);
			}
		}

		return $this->showPage($this->page404, []);
	}

	public function showPage($page_name, $params) {
		$path = $this->pages_path . $page_name;
		require $path;
	}
}
?>