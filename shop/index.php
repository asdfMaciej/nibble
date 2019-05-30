<?php
include_once __DIR__ . "/env/config.php";
include_once ROOT_PATH . "/application/app.php";

$router = new Router();
$router->page404 = "404.php";

$router->route('', 'index.php');
$router->route('category(?:\/([^\/]+)\/?|\/?)', 'category.php');
$router->route('order(?:\/([^\/]*)\/?|\/?)', 'order.php');
$router->route('product\/([^\/]+)\/?', 'product.php');
$router->route('basket\/?', 'basket.php');
$router->route('recommendations\/?', 'recommendations.php');
$router->execute();
?>