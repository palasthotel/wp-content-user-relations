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

	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		add_action("wp_ajax_".self::ACTION_FIND_CONTENTS, array($this, "find_contents"));
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
					"find" => $ajax_url.self::ACTION_FIND_CONTENTS,
				),
			)
		);
	}

	/**
	 * find contents
	 */
	function find_contents(){

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
}