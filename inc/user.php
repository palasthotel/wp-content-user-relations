<?php

namespace ContentUserRelations;


class User {
	public function __construct(Plugin $plugin) {
//		add_action('pre_get_users')
		add_action( 'delete_user', array($this, 'delete_user'), 10, 2 );
	}
	function delete_user($user_id, $old_user_data){

		//TODO:  delete connections on user delete

	}
}