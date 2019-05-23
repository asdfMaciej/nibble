<?php
$production = false;

define("SESSION_NAME", "kaszkowiakshop");

if ($production) {
	define("DB_HOST", "localhost");
	define("DB_USERNAME", "");
	define("DB_PASSWORD", "");
	define("DB_DATABASE", "");

	define('ROOT_PATH', '/root');
	define('PATH_PREFIX', '/sklep');

	define('DEBUG', False);
} else {
	define("DB_HOST", "localhost");
	define("DB_USERNAME", "root");
	define("DB_PASSWORD", "");
	define("DB_DATABASE", "sklep");

	define('ROOT_PATH', $_SERVER["DOCUMENT_ROOT"] . "/shop");
	define('PATH_PREFIX', "/shop");
	define('DEBUG', True);
}

session_name(SESSION_NAME);
session_start();
?>