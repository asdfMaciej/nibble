<?php
namespace Web\Pages;
use \PDO;
use \Model\Category;

include_once ROOT_PATH . "/application/app.php";

class Index extends \ShopBuilder {

	public function init() {
		$this->metadata->setTitle("404");
	}

	public function content() {
		$this->response->addTemplate("codes/404.php", []);

		$where = [];
		$c = new Category($this->database);

		$slug = "zaglada";
		$q= $c->select("children.*")
			->from($c, "category")
			->leftJoin($c, "children", "children.sub = category.id")
			->where("category.key_pl = :slug")
			->setParameter(":slug", $slug)
			->execute($this->database)
			->getAll();
		var_dump($q);
	}
}

new Index();
?>