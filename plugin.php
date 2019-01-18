<?php
/**
 * Plugin Name: Content User Relations
 * Plugin URI: https://palasthotel.de
 * Description: Relate user states to your contents
 * Version: 1.1.0
 * Author: Palasthotel <edward.bock@palasthotel.de>
 * Author URI: https://palasthotel.de
 * Text Domain: content-user-relations
 * Domain Path: /languages
 * Requires at least: 4.0
 * Tested up to: 5.0.3
 * License: http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @copyright Copyright (c) 2018, Palasthotel
 * @package Palasthotel\ContentUserRelations
 */

namespace ContentUserRelations;


class Plugin {

	const DOMAIN = "content-user-relations";

	const HANDLE_API_JS = "content-user-relations-js-api";
	const HANDLE_USER_PROFILE_JS = "content-user-relations-js-user-profile";
	const HANDLE_POST_META_BOX_STYLE = "content-user-relations-style";
	const HANDLE_POST_META_BOX_JS = "content-user-relations-js-meta-box";
	const HANDLE_POST_META_BOX_JS_EXTENSION = "content-user-relations-js-meta-box-ext-%d";

	/**
	 * filters
	 */
	const FILTER_QUERY_RESULT = "content_user_relations_query_result";
	const FILTER_QUERY_MAP_RESULT = "content_user_relations_query_map_result";
	const FILTER_POST_META_BOX_USER_RELATIONS = "content_user_relations_post_meta_box_user_relations";
	const FILTER_AJAX_WP_USERS_QUERY_ARGS = "content_user_relations_ajax_wp_users_query_args";
	const FILTER_AJAX_USER = "content_user_relations_ajax_user";
	const FILTER_AJAX_USERS_RESPONSE = "content_user_relations_ajax_users";
	const FILTER_META_BOX_EXTENSION_SCRIPS = "content_user_relations_meta_box_extension_scripts";

	/**
	 * actions
	 */
	const ACTION_ADD_CONTENT_USER_RELATION_BEFORE = "content_user_relations_add_relation_before";
	const ACTION_ADD_CONTENT_USER_RELATION_AFTER = "content_user_relations_add_relation_after";
	const ACTION_REMOVE_CONTENT_USER_RELATION_BEFORE = "content_user_relations_remove_relation_before";
	const ACTION_REMOVE_CONTENT_USER_RELATION_AFTER = "content_user_relations_remove_relation_after";
	const ACTION_CONTENT_USER_RELATIONS_SAVED = "content_user_relations_saved_relations";

	/**
	 * @var Plugin|null
	 */
	private static $instance = null;
	/**
	 * @return \ContentUserRelations\Plugin
	 */
	static function instance(){
		if(self::$instance == null) self::$instance = new Plugin();
		return self::$instance;
	}

	/**
	 * Plugin constructor.
	 */
	private function __construct() {

		$this->url = plugin_dir_url(__FILE__);
		$this->path = plugin_dir_path(__FILE__);
		$this->basename = plugin_basename(__FILE__);

		load_plugin_textdomain(
			Plugin::DOMAIN,
			FALSE,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		// migration
		require_once dirname(__FILE__)."/inc/migrate/migrate-init.php";
		$this->migrate = new MigrateInit();

		//base functions and classes
		require_once dirname(__FILE__)."/inc/database/db.php";
		require_once dirname(__FILE__)."/inc/database/query-conditions.php";
		require_once dirname(__FILE__)."/inc/database/query.php";

		//WP_User_Query extension
		require_once dirname(__FILE__)."/inc/wp-user-query-extension.php";
		$this->wpUserQueryExtension = new WPUserQueryExtension($this);

		// post query extension
		require_once dirname(__FILE__)."/inc/wp-post-query-extension.php";
		$this->wpPostQueryExtension = new WPPostQueryExtension($this);

		// settings page
		require_once dirname(__FILE__).'/inc/settings.php';
		$this->settings = new Settings($this);

		// adds relations to post
		require_once dirname(__FILE__)."/inc/post.php";

		// post edit meta box
		require_once dirname(__FILE__)."/inc/post-meta-box.php";
		$this->postMetaBox = new PostMetaBox($this);

		require_once dirname(__FILE__)."/inc/user-profile.php";
		$this->userProfile = new UserProfile($this);

		require_once dirname(__FILE__)."/inc/ajax.php";
		$this->ajax = new Ajax($this);

		/**
		 * type and state settings
		 */


		/**
		 * on activate or deactivate plugin
		 */
		register_activation_hook( __FILE__, array( $this, "activation" ) );
	}

	/**
	 * on plugin activation
	 */
	function activation() {
		// create tables
		Database\createTables();
	}
}
Plugin::instance();

require_once dirname(__FILE__).'/public-functions.php';