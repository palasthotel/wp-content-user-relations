<?php


namespace ContentUserRelations;


class CUR_WP_User_Query extends \WP_User_Query{
	protected function get_search_sql( $string, $cols, $wild = false ) {
		$sql = parent::get_search_sql( $string, $cols, $wild );
		return 'OR '.substr($sql, 4);
	}
}