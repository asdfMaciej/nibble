<?php
namespace Model;
class History extends \SessionModel {
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
		if (is_null($element["name"])) {
			return;
		}
		$this->viewed_products[] = $element;
	}

	public function addCategory($category) {
		$element = $category->asHistoryItem();
		if (is_null($element["name"])) {
			return;
		}
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
				$seen = intval($product["seen_on"]);
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