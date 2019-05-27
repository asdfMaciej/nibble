<?php
namespace Web\Pages;
use Boilerplate\User;
use \PDO;
include_once ROOT_PATH . "/application/app.php";

class Index extends \WebBuilder {
	public function init() {
		$this->metadata->setTitle("Index");
	}

	public function content() {
		
	}
}

new Index();
?>