<?php
namespace Model;
class Category extends \DBModel {
	protected static $table_name = "category";
	protected static $primary_key = "id";
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
			"level" => $this->level,
			"seen_on" => strtotime("now")
		];

		return $item;
	}

	public function isEmpty() {
		return is_null($this->id);
	}

	public static function getProducts($db, $slug) {
		$rows = static::select("product.*")
					->from(static::class, "category")
					->leftJoin(ProductConnection::class, "connection",
								"category.id = connection.cid")
					->innerJoin(Product::class, "product",
								"product.id = connection.pid")
					->where("category.key_pl = :slug")
					->setParameter(":slug", $slug)
					->execute($db)
					->getAll();
		$products = [];
		foreach ($rows as $product) {
			$products[] = Product::fromArray($product);
		}

		return $products;
	}

	public static function getCategory($db, $slug) {
		return static::getSingleItem($db, ["slug" => $slug]);
	}

	public static function getChildCategories($db, $slug="") {
		$rows = static::select("child.*");
									
		if ($slug !== "") {
			$rows->from(static::class, "category")
				->leftJoin(static::class, "child", "child.sub = category.id")
				->where("category.key_pl = :slug")
				->setParameter(":slug", $slug);
		} else {
			$rows->from(static::class, "child")
				->where("child.sub = 0");
		}

		$rows = $rows->execute($db)->getAll();

		$categories = [];
		foreach ($rows as $category) {
			$categories[] = static::fromArray($category);
		}

		return $categories;
	}
}
?>