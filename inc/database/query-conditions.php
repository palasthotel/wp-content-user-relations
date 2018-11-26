<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 30.01.18
 * Time: 15:22
 */

namespace ContentUserRelations\Database;


class QueryConditions {

	private $args = array();
	private $sql = null;

	/**
	 * QueryConditions constructor.
	 *
	 * args may have the structure of
	 * simple: array( 'relation' => 'AND|OR', 'type' => 'type_slug|NULL', 'state' => 'state_slug')
	 * complex: array('relation' => 'AND|OR', $simple, $simple2, ...)
	 * complex^2: array('relation' => 'AND|OR', $complex, $simple, ...)
	 *
	 * @param array $args
	 */
	public function __construct($args) {
		$this->args = $args;
	}

	/**
	 * computed sql conditions string
	 * @return string
	 */
	function get_sql(){
		if($this->sql == null){
			$this->sql = $this->parseConditions($this->args);
		}
		return $this->sql;
	}

	/**
	 * @param $args
	 * @return string
	 */
	private function parseConditions($args){
		$sub_wheres = array();

		// both cases its a relation
		$relation = "AND";
		if(isset($args["relation"]) && strtoupper($args["relation"]) == "OR") $relation = "OR";

		foreach ($args as $key => $val){
			if(is_array($val)){
				$condition = $this->parseConditions($val);
				if("" == $condition) continue;
				$sub_wheres[] = $condition;
				continue;
			}
			switch ($key){
				case "state_slug":
				case "type_slug":
				case "state_name":
				case "type_name":
					$sub_wheres[] = $key." = '".esc_sql($val)."'";
					continue;
				case "id": // relation id
				case "post_id":
				case "user_id":
				case "type_id":
				case "state_id":
				case "typestate_id":
					$sub_wheres[] = $key." = ".intval($val);
					continue;
				case "relation":
					if(strtoupper($val) == "OR") $relation = "OR";
					continue;
			}
		}

		// no condition found
		if(count($sub_wheres) < 1) return "";

		// return all conditions
		return "(".implode(" $relation ", $sub_wheres).")";

	}
}