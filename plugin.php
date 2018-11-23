<?php
/**
 * Plugin Name: Content User Relations
 * Plugin URI: https://palasthotel.de
 * Description: Relate user states to your contents
 * Version: 1.0.0
 * Author: Palasthotel <edward.bock@palasthotel.de>
 * Author URI: https://palasthotel.de
 * Text Domain: content-user-relations
 * Domain Path: /languages
 * Requires at least: 4.0
 * Tested up to: 4.9.2
 * License: http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @copyright Copyright (c) 2018, Palasthotel
 * @package Palasthotel\ContentUserRelations
 */

namespace ContentUserRelations;


class Plugin {

	const DOMAIN = "content-user-relations";

	const HANDLE_API_JS = "content-relations-js-api";
	const HANDLE_USER_PROFILE_JS = "content-relations-js-user-profile";

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

		// migration
		require_once dirname(__FILE__)."/inc/migrate/migrate-init.php";
		$this->migrate = new MigrateInit();

		//base functions and classes
		require_once dirname(__FILE__)."/inc/tables.php";
		require_once dirname(__FILE__)."/inc/query-conditions.php";
		require_once dirname(__FILE__)."/inc/query.php";

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

		// Add a clone of the List Tables Class
        require_once dirname(__FILE__)."/inc/render-table.php";

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
		createTables();
	}
}
Plugin::instance();

require_once dirname(__FILE__).'/public-functions.php';