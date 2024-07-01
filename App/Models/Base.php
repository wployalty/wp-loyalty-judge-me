<?php

namespace Wljm\App\Models;

abstract class Base {
	public static $tables, $field_list = array();
	static protected $db;
	protected $table = null, $primary_key = null, $fields = array();

	function __construct() {
		global $wpdb;
		self::$db = $wpdb;
	}

	function getTableName() {
		return $this->table;
	}

	function getTablePrefix() {
		return self::$db->prefix;
	}

	function getWhere( $where, $select = '*', $single = true ) {
		if ( is_array( $select ) || is_object( $select ) ) {
			$select = implode( ',', $select );
		}
		if ( empty( $select ) ) {
			$select = '*';
		}
		$query = "SELECT {$select} FROM {$this->table} WHERE {$where};";
		if ( $single ) {
			return self::$db->get_row( $query, OBJECT );
		} else {
			return self::$db->get_results( $query, OBJECT );
		}
	}
}