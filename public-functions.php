<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 02.02.18
 * Time: 13:21
 */

/**
 * Class ContentUserRelationsQuery
 * for public use
 */
class ContentUserRelationsQuery extends \ContentUserRelations\Query{};

/**
 * @param int $user_id WP_User ID
 * @param int $post_id WP_Post ID
 * @param string $type_slug
 * @param string $state_slug
 *
 * @return false|int
 */
function content_user_relations_add_relation($user_id, $post_id, $type_slug, $state_slug){
	return \ContentUserRelations\addRelation($user_id, $post_id, $type_slug, $state_slug);
}

/**
 * @param int $user_id WP_User ID
 * @param int $post_id WP_Post ID
 * @param string $type_slug
 * @param string $state_slug
 *
 * @return bool|int
 */
function content_user_relation_remove_relation($user_id, $post_id, $type_slug, $state_slug){
	return \ContentUserRelations\removeRelation($user_id, $post_id, $type_slug, $state_slug);
}