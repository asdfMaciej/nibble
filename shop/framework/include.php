<?php
$required_constants = [
	"ROOT_PATH", "PATH_PREFIX",
	"DB_HOST", "DB_USERNAME", "DB_PASSWORD", "DB_DATABASE"
];

foreach ($required_constants as $constant) {
	if (!defined($constant)) {
		$error = "Declare this constant for framework to work: $constant";
		throw new Exception($error);
		exit;
	}
}

include_once __DIR__ . "/orm.php";
include_once __DIR__ . "/templating_engine.php";
include_once __DIR__ . "/response_builders.php";
include_once __DIR__ . "/database_class.php";
include_once __DIR__ . "/router.php";
?>