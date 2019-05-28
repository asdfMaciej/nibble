<?php
class DBClass {
	private $connection = null;

	private $host = DB_HOST;
	private $username = DB_USERNAME;
	private $password = DB_PASSWORD;
	private $database = DB_DATABASE;

	public function getConnection() {
		if (is_null($this->connection)) {
			try {
				$call = "mysql:host=" . $this->host . ";dbname=" . $this->database;
				$this->connection = new PDO($call, $this->username, $this->password);
				$this->connection->exec("set names utf8");
			} catch(PDOException $exception) {
				$this->connection = null;
				echo "Error: " . $exception->getMessage();
			}
		}

		return $this->connection;
	}
}
?>