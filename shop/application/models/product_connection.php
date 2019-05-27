<?php
namespace Model;
class ProductConnection extends \DBModel {
	protected static $table_name = "product_connection";
	protected static $primary_key = "id";

	public $sort;
	public $id;
	public $pid;
	public $cid;
}
?>