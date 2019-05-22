<?php
namespace Web\Pages;
use Boilerplate\User;
use \PDO;
include_once __DIR__ . "/../env/config.php";

include_once ROOT_PATH . "/config/database.php";
include_once ROOT_PATH . "/config/functions.php";
include_once ROOT_PATH . "/config/builder.php";

class Index extends \WebBuilder {
	public function init() {
		$this->metadata->setTitle("Index");
		$this->addAction("login", "on_login");
		$this->addAction("logout", "on_logout");
	}

	public function content() {
		$this->response->addTemplate("welcome.php", []);
		$this->response->addTemplate("list_items.php", [
			"items" => [123, 1525, 5351351, 14]
		]);
		$this->response->addTemplate("messages/index_welcome.php", [
			"date" => date('d M Y'),
			"day" => date('w')
		]);
	}

	protected function on_login() {
		return True;
	}

	protected function on_logout() {
		return True;
	}
}

new Index();
?>