<?php

namespace ContentUserRelations\Database;

/**
 * get a relation by relation id
 *
 * @param $relation_id
 *
 * @return array|null|object
 */
function getRelationById($relation_id){
	global $wpdb;
	$allRelationsSQL = getAllRelationsSQL();
	return $wpdb->get_row(
		"SELECT * FROM ( $allRelationsSQL ) as a WHERE id = ".intval($relation_id)
	);
}

/**
 * @param string $type_slug
 * @param string $state_slug
 *
 * @return bool|int
 */
function getTypeStateId( $type_slug, $state_slug){
	$typeState = getTypeState($type_slug, $state_slug);
	if($typeState != null) return $typeState->id;
	return false;
}

/**
 * @param string $type_slug
 * @param string $state_slug
 *
 * @return object|null
 */
function getTypeState( $type_slug, $state_slug){
	global $wpdb;
	$typestatesSql = getTypeStatesSQL();
	$prepared = $wpdb->prepare(
		"SELECT * FROM ( $typestatesSql ) as ts WHERE state_slug = %s AND type_slug = %s;",
		array(
			$state_slug,
			$type_slug
		)
	);
	return $wpdb->get_row($prepared);
}

/**
 * @param $typestate_id
 *
 * @return null|object
 */
function getTypeStateById($typestate_id){
	global $wpdb;
	$typestatesSql = getTypeStatesSQL();
	$prepared = $wpdb->prepare(
		"SELECT * FROM ( $typestatesSql ) as ts WHERE id = %d;",
		array(
			$typestate_id,
		)
	);
	return $wpdb->get_row($prepared);
}

/**
 * get relation state by column
 * @param $val
 * @param string $column
 *
 * @return mixed
 */
function getRelationState($val, $column="id"){
	$states = getRelationStates();
	return $states[ array_search( $val, array_column( $states, $column ) ) ];
}

/**
 * get relation type by column
 * @param $val
 * @param string $column
 *
 * @return mixed
 */
function getRelationType($val, $column="id"){
	$types = getRelationTypes();
	return $types[ array_search( $val, array_column( $types, $column ) ) ];
}

/**
 * get all relation states
 * by type (optionally)
 * @param null|int $type_id
 *
 * @return array
 */
function getRelationStates($type_id = null){
	global $wpdb;
	$states = tablename(TABLE_STATES);
	$where = "";
	if($type_id != null){
		$typestates = tablename(TABLE_TYPESTATES);
		$where = "WHERE id IN (SELECT state_id FROM $typestates WHERE type_id = ".intval($type_id).")";
	}
	return $wpdb->get_results(
		"SELECT * FROM $states $where"
	);
}



/**
 * array of all relation types
 * @return array
 */
function getRelationTypes($state_id = null){
	global $wpdb;
	$types = tablename(TABLE_TYPES);
	$where = "";
	if($state_id != null){
		$typestates = tablename(TABLE_TYPESTATES);
		$where = "WHERE id IN (SELECT type_id FROM $typestates WHERE state_id = ".intval($state_id).")";
	}
	return $wpdb->get_results(
		"SELECT * FROM $types $where"
	);
}

/**
 * map with type key of all relations
 *
 * @return array("type1" => array(relations...),"type2=>array(relations...))
 */
function getRelationsMap(){
	$list = getRelationsList();
	$map = array();
	foreach ($list as $relation){
		if(!isset($map[$relation->type_slug])) $map[$relation->type_slug] = array();
		$map[$relation->type_slug][] = $relation;
	}
	return $map;
}

/**
 * list of all available relations
 * @return array(relations...)
 */
function getRelationsList(){
	global $wpdb;
	$allRelationsSQL = getAllRelationsSQL();
	return $wpdb->get_results(
		"SELECT * FROM ( $allRelationsSQL ) as a ORDER BY type_id ASC, state_id ASC"
	);
}

/**
 * @return array(typestate,...)
 */
function getRelationTypeStatesList(){
	global $wpdb;
	$allTypeStateSql = getTypeStatesSQL();
	return $wpdb->get_results(
		"SELECT * FROM ( $allTypeStateSql ) as a ORDER BY type_id ASC, state_id ASC"
	);
}

/**
 * returns array of relations (user_id, type, state)
 * @param $post_id
 * @return array
 */
function getPostRelations($post_id){
	global $wpdb;
	$allRelationsSQL = getAllRelationsSQL();
	return $wpdb->get_results(
		"SELECT * FROM ( $allRelationsSQL ) as a WHERE post_id = ".intval($post_id)
	);
}

/**
 * return alls relations for user
 * @param $user_id
 * @return array
 */
function getUserRelations($user_id){
	global $wpdb;
	$allRelationsSQL = getAllRelationsSQL();
	return $wpdb->get_results(
		"SELECT * FROM ($allRelationsSQL) as a WHERE user_id = ".intval($user_id)
	);
}

/**
 * @param $user_id
 * @param $post_id
 * @param $typestate_id
 *
 * @return null|int
 */
function getRelationId($user_id, $post_id, $typestate_id){
	global $wpdb;
	$relations = tablename(TABLE_RELATIONS);
	return $wpdb->get_var(
		"SELECT id FROM $relations WHERE user_id = $user_id AND post_id = $post_id AND typestate_id = $typestate_id"
	);
}

/**
 * update a relation
 *
 * @param $relation_id
 * @param $user_id
 * @param $post_id
 * @param $typestate_id
 *
 * @return false|int
 */
function updateRelation($relation_id, $user_id, $post_id, $typestate_id){
	global $wpdb;
	return $wpdb->update(
		tablename(TABLE_RELATIONS),
		array(
			'user_id' => $user_id,
			'post_id' => $post_id,
			'typestate_id' => $typestate_id,
		),
		array(
			"id" => $relation_id,
		),
		array(
			'%d',
			'%d',
			'%d',
		),
		array(
			'%d',
		)
	);
}

/**
 * add relation
 * @param $user_id
 * @param $post_id
 * @param $state_slug
 * @param $type_slug
 *
 * @return false|int
 */
function addRelation($user_id, $post_id, $type_slug, $state_slug){
	global $wpdb;
	do_action("content_user_relations_add_relation", $user_id, $post_id, $type_slug, $state_slug);
	$typestate_id = getTypeStateId( $type_slug, $state_slug);
	// there is no typestate so
	if($typestate_id == null) return false;
	return addRelationWithTypeState($user_id, $post_id, $typestate_id);
}

/**
 * add relation
 * @param $user_id
 * @param $post_id
 * @param $typestate_id
 *
 * @return false|int
 */
function addRelationWithTypeState($user_id, $post_id, $typestate_id){
	global $wpdb;
	/**
	 * add into table
	 */
	$wpdb->insert(
		tablename(TABLE_RELATIONS),
		array(
			"user_id" => $user_id,
			"post_id" => $post_id,
			"typestate_id" => $typestate_id
		),
		array(
			"%d",
			"%d",
			"%d",
		)
	);
	return $wpdb->insert_id;
}

/**
 * remove relation
 *
 * @param $user_id
 * @param $post_id
 * @param $state_slug
 * @param $type_slug
 *
 * @return int|bool
 */
function removeRelation($user_id, $post_id, $type_slug, $state_slug){
	do_action("content_user_relations_remove_relation", $user_id, $post_id, $type_slug, $state_slug);
	$typestate_id = getTypeStateId( $type_slug, $state_slug );
	if($typestate_id == null) return false;
	return removeRelationWithTypeState($user_id, $post_id, $typestate_id);
}

/**
 * @param $user_id
 * @param $post_id
 * @param $typestate_id
 *
 * @return false|int
 */
function removeRelationWithTypeState($user_id, $post_id, $typestate_id){
	global $wpdb;
	return $wpdb->delete(
		tablename(TABLE_RELATIONS),
		array(
			'user_id' => $user_id,
			'post_id' => $post_id,
			'typestate_id' => $typestate_id,
		),
		array(
			'%d',
			'%d',
			'%d',
		)
	);
}

/**
 * connect state and type
 * @param $type_id
 * @param $state_id
 *
 * @return false|int
 */
function addRelationTypeState($type_id, $state_id){
	global $wpdb;
	return $wpdb->insert(
		tablename(TABLE_TYPESTATES),
		array(
			"type_id" =>  $type_id,
			"state_id" => $state_id,
		),
		array(
			'%d',
			'%d',
		)
	);
}

/**
 * delete type state connection
 * @param $type_id
 * @param $state_id
 *
 * @return false|int
 */
function removeRelationTypeState($type_id, $state_id){
	global $wpdb;
	return $wpdb->delete(
		tablename(TABLE_TYPESTATES),
		array(
			"type_id" => $type_id,
			"state_id" => $state_id,
		),
		array(
			'%d',
			'%d',
		)
	);
}

/**
 * add a new relation state
 *
 * @param $slug
 * @param $name
 *
 * @return bool|int
 */
function addRelationState($slug,$name){
	global $wpdb;
	return $wpdb->insert(
		tablename(TABLE_STATES),
		array(
			"slug" => $slug,
			"name" => $name,
		)
	);
}

/**
 * add a new relation type
 *
 * @param $slug
 * @param $name
 *
 * @return bool|int
 */
function addRelationType($slug,$name){
	global $wpdb;
	return $wpdb->insert(
		tablename(TABLE_TYPES),
		array(
			"slug" => $slug,
			"name" => $name,
		)
	);
}

/**
 * plugin table names without wordpress prefix
 */
const TABLE_STATES     = 'cur_states';
const TABLE_TYPES      = 'cur_types';
const TABLE_TYPESTATES = 'cur_typestates';
const TABLE_RELATIONS  = 'cur_relations';

/**
 * get tablename in database
 * @param $tablename_const
 *
 * @return string
 */
function tablename($tablename_const){
	global $wpdb;
	$prefix = $wpdb->prefix;
	return $prefix.$tablename_const;
}

/**
 * sql for connection states and types table over typestates table
 * @return string
 */
function getTypeStatesSQL(){
	$typestates = tablename(TABLE_TYPESTATES);
	$types = tablename(TABLE_TYPES);
	$states = tablename(TABLE_STATES);
	return "SELECT 
			    ts.id as id,  
				s.id as state_id, 
				s.slug as state_slug, 
				s.name as state_name, 
				t.id as type_id, 
				t.slug as type_slug, 
				t.name as type_name
				FROM $typestates as ts
				LEFT JOIN $types as t on ts.type_id = t.id
				LEFT JOIN $states as s ON ts.state_id = s.id
				";
}

/**
 * sql that connects all tables to one big relation table
 * @return string
 */
function getAllRelationsSQL(){
	$relations = tablename(TABLE_RELATIONS);
	$typestates = tablename(TABLE_TYPESTATES);
	$types = tablename(TABLE_TYPES);
	$states = tablename(TABLE_STATES);
	return "SELECT 
				r.id as id, 
				r.user_id as user_id, 
				r.post_id as post_id, 
				ts.id as typestate_id, 
				s.id as state_id, 
				s.slug as state_slug, 
				s.name as state_name, 
				t.id as type_id, 
				t.slug as type_slug, 
				t.name as type_name
				FROM $relations as r 
				LEFT JOIN $typestates as ts ON r.typestate_id = ts.id
				LEFT JOIN $types as t on ts.type_id = t.id
				LEFT JOIN $states as s ON ts.state_id = s.id
				";
}

/**
 * create tables
 */
function createTables(){
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	global $wpdb;
	$posts = $wpdb->posts;
	$users = $wpdb->users;
	$states = tablename(TABLE_STATES);
	$types = tablename(TABLE_TYPES);
	$typestates = tablename(TABLE_TYPESTATES);
	$relations = tablename(TABLE_RELATIONS);
	dbDelta("CREATE TABLE IF NOT EXISTS $states
		(
		 id bigint(20) unsigned auto_increment ,
		 slug varchar(32) NOT NULL ,
		 name varchar(32) NOT NULL,
		 primary key (id),
		 unique key (slug)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

	dbDelta("CREATE TABLE IF NOT EXISTS $types
		(
		 id bigint(20) unsigned auto_increment ,
		 slug varchar(32) NOT NULL ,
		 name varchar(32) NOT NULL,
		 primary key (id),
		 unique key (slug)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

	dbDelta("CREATE TABLE IF NOT EXISTS $typestates
		(
		 id bigint(20) unsigned auto_increment ,
		 type_id bigint(20) UNSIGNED NOT NULL,
		 state_id bigint(20) UNSIGNED NOT NULL,
		 primary key (id),
		 unique key typestate (type_id, state_id),
		 CONSTRAINT `to_type_relation` FOREIGN KEY (`type_id`) REFERENCES $types (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
		 CONSTRAINT `to_state_relation` FOREIGN KEY (`state_id`) REFERENCES $states (`id`) ON DELETE CASCADE ON UPDATE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

	dbDelta("CREATE TABLE IF NOT EXISTS $relations
		(
		 id bigint(20) UNSIGNED auto_increment,
		 typestate_id bigint(20) UNSIGNED NOT NULL ,
		 post_id bigint(20) UNSIGNED NOT NULL ,
		 user_id bigint(20) UNSIGNED NOT NULL ,
		 primary key (id),
		 unique key relation (typestate_id, post_id, user_id),
		 key (typestate_id),
		 CONSTRAINT `to_typestate_relation` FOREIGN KEY (`typestate_id`) REFERENCES $typestates (`id`) ON DELETE CASCADE ON UPDATE NO ACTION ,
		 CONSTRAINT `to_post_relation` FOREIGN KEY (`post_id`) REFERENCES $posts (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION ,
		 CONSTRAINT `to_user_relation` FOREIGN KEY (`user_id`) REFERENCES $users (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION 		 
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
}