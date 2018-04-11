<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 30.01.18
 * Time: 14:23
 */

namespace ContentUserRelations;


class PostMetaBox {

	/**
	 * PostMetaBox constructor.
	 *
	 * @param \ContentUserRelations\Plugin $plugin
	 */
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		$this->plugin = $plugin;
		add_action( 'add_meta_boxes', array($this,'add_meta_box'),10 ,2 );
	}

	/**
	 * register meta box
	 */
	function add_meta_box($post_type, $post){
		if($this->plugin->settings->isPostTypeEnabled($post_type)){
			wp_enqueue_style(
				"content-user-relations-style",
				$this->plugin->url . "/css/meta-box.css"
			);
			add_meta_box(
				'content-user-relations',
				__( 'Content user relations', Plugin::DOMAIN ),
				array($this,'render'),
				$post_type
			);
		}
	}

	/**
	 * render content
	 */
	function render(){

		$relations = new Query(array(
			"post_id" => get_the_ID(),
		));

		echo '<ul class="cur-users">';
		foreach ($relations->getUserMap() as $user_id => $relations){
			$user = get_user_by("ID", $user_id);
			echo "<li class='cur-users__item'>";
			echo "<span class='cur-user__name'>".$user->display_name."</span>";
			echo "<ul class='cur-relations'>";


			foreach ($relations as $relation){
				$type = $relation->type_name;
				$state = $relation->state_name;
				echo "<li class='cur-relations__item'>$type: $state</li>";
			}
			echo "</ul>";
			echo "</li>";
		}
		echo '</ul>';

//		$query = new \WP_User_Query(array(
//			"content_relations"=> array(
//				"post_id" => get_the_ID(),
//			)
//		));
//		$results = $query->get_results();
//
//		echo "<ul>";
//		foreach ($results as $user){
//			$id = $user->ID;
//			$name = $user->display_name;
//
//			$relations = new Query(array(
//				"user_id" => $id,
//				"post_id" => get_the_ID(),
//			));
//
//			echo "<li><span title='ID: $id'>$name</span>";
//			echo "<ul style='margin-left: 30px;'>";
//			foreach ($relations->get() as $relation){
//				$type = $relation->type_name;
//				$state = $relation->state_name;
//				echo "<li>$type -> $state</li>";
//			}
//			echo "</ul>";
//			echo "</li>";
//		}
//		echo "</ul>";

//		$types = getRelationTypes();
//
//		echo "<ul class='cur-types'>";
//		foreach( $types as $type){
//			$typeName = $type->name;
//			$typeSlug = $type->slug;
//			$states = getRelationStates($type->id);
//			if(count($states)<1) continue;
//			echo "<li class='cur-types__item'>";
//			echo "<div class='cur-types__item--name'>$typeName <small>[$typeSlug]</small></div>";
//			echo "<ul class='cur-states'>";
//			foreach($states as $state){
//				$stateName = $state->name;
//				echo "<li class='cur-states__item'>";
//				echo "<div class='cur-states__item--name'>$stateName</div>";
//
//
//				echo "</li>";
//			}
//			echo "</ul>";
//			echo "</li>";
//		}
//		echo "</ul>";


	}
}