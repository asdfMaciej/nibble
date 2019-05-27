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
	}

	public function content() {
		
	}
}

new Index();
?>