<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 30.01.18
 * Time: 15:09
 */

namespace ContentUserRelations\Database;


use ContentUserRelations\Plugin;

class Query {

	private $args = array();
	private $relations = NULL;

	/**
	 * Query constructor.
	 *
	 * @param [user_id, WP_User] $args
	 */
	public function __construct( $args ) {
		$this->args = $args;
	}

	/**
	 * get user data
	 *
	 * @return array [relation1, relation2, ...]
	 */
	function get() {
		if ( $this->relations == NULL ) {
			$this->relations = $this->execute();
		}

		return $this->relations;
	}

	/**
	 * @param string $prop post_id,user_id,type_id,type_slug,state_id,state_slug
	 *
	 * @return array [propValue1=>[relation,...], ...]
	 */
	function getMapBy( $prop ) {
		$map = array();
		foreach ( $this->get() as $relation ) {
			if ( ! isset( $map[ $relation->{$prop} ] ) ) {
				$map[ $relation->{$prop} ] = array();
			}
			$map[ $relation->{$prop} ][] = $relation;
		}

		return apply_filters( Plugin::FILTER_QUERY_MAP_RESULT, $map, $prop);
	}

	/**
	 * @return array ["type_slug" => [relation,...], "type_slug2" =>
	 *     [relation,...],...]
	 */
	function getTypeMap() {
		return $this->getMapBy( "type_slug" );
	}

	/**
	 * @return array [userid => [relation,...],...]
	 */
	function getUserMap() {
		return $this->getMapBy( "user_id" );
	}

	/**
	 * get user data from db
	 *
	 * @return array
	 */
	private function execute() {

		// list of all available relations
		if ( isset( $this->args["list"] ) && true == $this->args["list"] ) {
			return getRelationTypeStatesList();
		}

		// get all states for type
		if ( isset( $this->args["for_type"] ) ) {
			return getRelationStates( $this->args["for_type"] );
		}

		// get all types for state
		if ( isset( $this->args["for_state"] ) ) {
			return getRelationTypes( $this->args["for_state"] );
		}

		global $wpdb;
		$queryConditions = new QueryConditions( $this->args );
		$conditions      = $queryConditions->get_sql();
		$where           = "";
		if ( "" != $conditions ) {
			$where = "WHERE $conditions";
		}
		$allRelationsSql = getAllRelationsSQL();

		return apply_filters(
			\ContentUserRelations\Plugin::FILTER_QUERY_RESULT,
			$wpdb->get_results(
				"SELECT * FROM ($allRelationsSql) as relations $where ORDER BY type_name, state_name"
			)
		);
	}
}