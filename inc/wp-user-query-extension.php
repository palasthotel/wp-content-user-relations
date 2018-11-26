<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 30.01.18
 * Time: 10:26
 */

namespace ContentUserRelations;


class WPUserQueryExtension {

	const ARG_CONTENT_RELATIONS = "content_relations";

	/**
	 * WPUserQueryExtension constructor.
	 *
	 * @param \ContentUserRelations\Plugin $plugin
	 */
	public function __construct(Plugin $plugin) {
		// fires after query has been parsed
		add_action('pre_user_query', array($this, 'manipulate'));
	}

	/**
	 * get user query as an reference
	 * @param \WP_User_Query $query
	 */
	function manipulate(\WP_User_Query $query){

		// check if there are content relations args
		if(!isset($query->query_vars[self::ARG_CONTENT_RELATIONS]) || !is_array($query->query_vars[self::ARG_CONTENT_RELATIONS])) return;

		/**
		 * for valid args have a look in QueryConditions class
		 */
		$queryConditions = new Database\QueryConditions($query->query_vars[self::ARG_CONTENT_RELATIONS]);
		$conditions = $queryConditions->get_sql();

		if("" == $conditions) return;

		$allRelations = Database\getAllRelationsSQL();
		$relation_where = " ID IN ( SELECT user_id FROM ($allRelations) as contentrelations WHERE $conditions ) ";

		$and = "";
		if($query->query_where != "") $and = " AND";

		$query->query_where.= $and.$relation_where;

	}



}