<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 30.01.18
 * Time: 10:26
 */

namespace ContentUserRelations;


class WPPostQueryExtension {

	const ARG_USER_RELATABLE = "user_relatable";
	const ARG_RELATED_TO_USER = "related_to_user";

	/**
	 * WPPostQueryExtension constructor.
	 *
	 * @param \ContentUserRelations\Plugin $plugin
	 */
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		// find all posts with user relations of state AND|OR type
		// $args["user_relations"]
		// see
		// https://github.com/palasthotel/wp-additional-authors/blob/master/inc/query-manipulation.php
		add_action('pre_get_posts', array($this, 'pre_get_posts'));
		add_filter('posts_where', array($this, 'posts_where'), 10 , 2);
	}

	/**
	 * @param \WP_Query $query
	 */
	function pre_get_posts($query){
		if( isset($query->query_vars[self::ARG_USER_RELATABLE]) && true == $query->query_vars[self::ARG_USER_RELATABLE]){
			$set_type = $query->query_vars["post_type"];
			$enabled_post_types = $this->plugin->settings->getPostTypesEnabled();

			if($set_type == null || 'any' == $set_type){
				// if not set explicit or any is set use enabled ones for relations
				$query->query_vars["post_type"] = $enabled_post_types;
				return;
			}

			if(is_array($set_type)){
				$post_types = array();
				foreach ($set_type as $t){
					if(array_search($t, $enabled_post_types)) $post_types[] = $t;
				}
				// only those which are enabled survived
				$query->query_vars["post_type"] = $post_types;
			} else {
				if(array_search($set_type, $enabled_post_types)) return;
				// it set post type is not in whitelist, than we should not return any posts
				$query->query_vars["post_type"] = "content-user-relations-restricted-post-type-just-some-stupid-unique-string-to-prevent-results";
			}
		}
	}

	/**
	 * @param string $where
	 * @param \WP_Query $query
	 *
	 * @return string
	 */
	function posts_where($where, $query){

		if(isset($query->query_vars[self::ARG_RELATED_TO_USER])){
			$user_id = intval($query->query_vars[self::ARG_RELATED_TO_USER]);
			$user = get_userdata($user_id);
			if($user !== false){
				$allRelationsSql = Database\getAllRelationsSQL();
				if($where != "") $where.=" AND";
				$where.=" ID IN (SELECT post_id from ($allRelationsSql) as cur WHERE cur.user_id = $user_id ) ";
			}
		}

//		if( isset($query->query_vars["userRelatable"]) && true == $query->query_vars["userRelatable"]){
//
//			$allRelationsSql = getAllRelationsSQL();
//
//			$relationsWhere = " AND ID in ( SELECT post_id "
//
//
//		}

		return $where;
	}

}