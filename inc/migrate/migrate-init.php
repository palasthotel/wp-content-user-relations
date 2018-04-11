<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 07.02.18
 * Time: 15:52
 */

namespace ContentUserRelations;


class MigrateInit {
	public function __construct() {
		add_action('init', array($this, 'init'));
	}

	public function init(){
		if(class_exists('ph_destination')){
			require_once dirname(__FILE__)."/migrate-destination.php";
		}
	}
}