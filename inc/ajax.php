<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 02.02.18
 * Time: 10:00
 */

namespace ContentUserRelations;


class Ajax {

	const ACTION_FIND_CONTENTS = "cur_find_contents";
	const ACTION_FIND_USERS = "cur_find_users";

	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		add_action("wp_ajax_".self::ACTION_FIND_CONTENTS, array($this, "find_contents"));
		add_action("wp_ajax_".self::ACTION_FIND_USERS, array($this, "find_users"));
		add_action('init', array($this,'init'));
	}

	/**
	 * on init register api to be available to enqueue it
	 */
	function init(){
		$ajax_url = admin_url('admin-ajax.php')."?action=";
		wp_register_script(
			Plugin::HANDLE_API_JS,
			$this->plugin->url."/js/api.js",
			array('jquery')
		);
		wp_localize_script(
			Plugin::HANDLE_API_JS,
			'ContentUserRelations_API',
			array(
				"ajaxurls" => array(
					"findContents" => $ajax_url.self::ACTION_FIND_CONTENTS,
					"findUsers" => $ajax_url.self::ACTION_FIND_USERS,
				),
			)
		);
	}

	/**
	 * find contents
	 */
	function find_contents(){

		$this->securityCheck();

		$search = sanitize_text_field($_GET["s"]);
		$query = new \WP_Query(array(
			's' => $search,
			'user_relatable' => true,
		));

		$response = array();
		while($query->have_posts()){
			$query->the_post();

			$response[] = array(
				"ID" => get_the_ID(),
				"post_title" => get_the_title(),
				"post_type" => get_post_type(),
			);


		}

		wp_send_json($response);

		// all contents that are available for relations

	}

	/**
	 * find contents
	 */
	function find_users(){

		$this->securityCheck();

		$search = sanitize_text_field($_GET["s"]);
		$users = new \WP_User_Query(array(
			"search" => "*$search*",
			"number" => 10,
		));

		$users_response = array();
		foreach($users->get_results() as $user){
			/**
			 * @var \WP_User $user
			 */
			$users_response[] = array(
				"ID" => $user->ID,
				"display_name" => $user->display_name,
				"user_email" => $user->user_email,
			);

		}

		wp_send_json(array(
			"users" => $users_response,
			"overall" => $users->get_total(),
		));

		// all contents that are available for relations

	}

	private function securityCheck(){
		if(!current_user_can("edit_posts")) die();
	}
}