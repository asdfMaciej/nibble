<?php
include_once __DIR__ . "/env/config.php";
include_once ROOT_PATH . "/application/app.php";

$uri = explode('?', $_SERVER['REQUEST_URI'], 2);
$path = $uri[0];

$pages = [
	"" => "pages/index.php",
	"/" => "pages/index.php",
	"404" => "pages/404.php",
	"basket" => [
		"index" => "pages/basket.php",
	],
	"category" => [
		"index" => "pages/category.php",
		"*" => "pages/category.php"
	],
	"order" => [
		"*" => "pages/order.php"
	],
	"product" => [
		"index" => "pages/product.php",
		"*" => "pages/product.php"
	]
];

$iter_pages = $pages;
$prefix = "";
$depth = 0;

$prefix_length = strlen(PATH_PREFIX);
$path = substr($path, $prefix_length);

$path_levels = explode("/", $path);

array_shift($path_levels); // 1st item
while (True) {
	if (sizeof($path_levels) <= $depth) {
		if (array_key_exists("index", $iter_pages)) {
			require $iter_pages["index"];
		} elseif (array_key_exists("*", $iter_pages)) {
			require $iter_pages["*"];
		} else {
			require $pages["404"];
		}
		break;
	}

	$_p = $path_levels[$depth];
	if (array_key_exists($_p, $iter_pages)) {
		if (is_array($iter_pages[$_p])) {
			$iter_pages = $iter_pages[$_p];
			$prefix .= $_p . "/";
			$depth += 1;
		} else {
			require $iter_pages[$_p];
			break;
		}
	} elseif ($_p == "" || $_p == "/") {
		if (array_key_exists("index", $iter_pages)) {
			require $iter_pages["index"];
		} elseif (array_key_exists("*", $iter_pages)) {
			require $iter_pages["*"];
		} else {
			require $pages["404"];
		}
		break;
	} elseif (array_key_exists("*", $iter_pages)) {
		require $iter_pages["*"];
		break;
	} else {
		require $pages["404"];
		break;
	}
}
?>