<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 30.01.18
 * Time: 14:59
 */

namespace ContentUserRelations;


class Post {
	public function __construct(Plugin $plugin) {
		add_action('the_post', array($this, 'the_post'));
	}

	/**
	 * @param \WP_Post $post
	 */
	function the_post($post){



	}

}