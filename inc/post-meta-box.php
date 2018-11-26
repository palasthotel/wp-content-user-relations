<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 30.01.18
 * Time: 14:23
 */

namespace ContentUserRelations;


use function ContentUserRelations\Database\getRelationsList;
use function ContentUserRelations\Database\getRelationTypeStatesList;

class PostMetaBox {

	const AUTOCOMPLETE_APP_ROOT_ID = "cur-user-autocomplete";
	const AUTOCOMPLETE_INPUT_NAME = "cur_user_autocomplete_input";
	const NEW_USER_ID = "cur_new_user_id";
	const NEW_TYPE_STATE_ID = "cur_new_type_state_id";

	/**
	 * PostMetaBox constructor.
	 *
	 * @param \ContentUserRelations\Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
		add_action('save_post', array($this, 'save_post'));
	}

	/**
	 * register meta box
	 */
	function add_meta_box( $post_type, $post ) {
		if ( $this->plugin->settings->isPostTypeEnabled( $post_type ) ) {
			wp_enqueue_style(
				"content-user-relations-style",
				$this->plugin->url . "/css/meta-box.css"
			);
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			wp_enqueue_script( Plugin::HANDLE_API_JS );
			wp_enqueue_script(
				Plugin::HANDLE_POST_META_BOX_JS,
				$this->plugin->url . "/js/meta-box.js",
				array(
					'jquery',
					'jquery-ui-autocomplete',
					Plugin::HANDLE_API_JS,
				),
				1,
				true
			);
			wp_localize_script( Plugin::HANDLE_POST_META_BOX_JS,
				"ContentUserRelations_MetaBox",
				array(
					'root_id' => self::AUTOCOMPLETE_APP_ROOT_ID,
					'autocomplete_input_name' => self::AUTOCOMPLETE_INPUT_NAME,
					'name_user_id_arr' => self::NEW_USER_ID,
					'name_type_state_id_arr' => self::NEW_TYPE_STATE_ID,
				)
			);
			add_meta_box(
				'content-user-relations',
				__( 'Content user relations', Plugin::DOMAIN ),
				array( $this, 'render' ),
				$post_type
			);
		}
	}

	/**
	 * render content
	 */
	function render() {

		$this->renderSearch();


		$relations = new Database\Query( array(
			"post_id" => get_the_ID(),
		) );

		$args = array();

		$args['singular'] = "Related Member";
		$args['plural']   = "Related Members";

		$columns           = array();
		$columns['name']   = __( 'User', 'ph' );
		$columns['groups'] = __( 'Group', 'ph' );

		$sortable_columns         = array();
		$sortable_columns['name'] = array( 'name', true );

		$items = array();

		foreach ( $relations->getUserMap() as $user_id => $relations ) {
			$item            = array();
			$user            = get_user_by( "ID", $user_id );
			$item['user_id'] = $user_id;
			$item['user']    = $user;
			$item['name']    = $user->user_login;
			foreach ( $relations as $relation ) {
				$item['relations'][] = $relation;
				$item['group'][]     = "<li>" . $relation->type_name . " – " . $relation->state_name . "</li>";
			}

			$item['groups'] = "<ul>" . implode( " ", $item['group'] ) . "</ul>";
			$items[]        = $item;

		}

		$table = new RenderTable( $args, $columns, $sortable_columns, $items );
		$table->display();


	}

	private function renderSearch() {

		$relations = getRelationTypeStatesList();

		if(count($relations) < 1) {
			echo "<p class='description'>No relations configured.</p>";
			return;
		}

		echo '<div id="'.self::AUTOCOMPLETE_APP_ROOT_ID.'">';

			echo "<label class='cur-relation-type-label' for='cur-state-type-select'>Relation: ";
			echo "<select id='cur-state-type-select'>";
			foreach ($relations as $rts){
				$name =  $rts->type_name." - ".$rts->state_name;
				echo "<option value='{$rts->id}'>$name</option>";
			}
			echo "</select>";
			echo "</label>";

			echo '<label for="'.self::AUTOCOMPLETE_INPUT_NAME.'">User: ';
			echo '<input type="text" id="'.self::AUTOCOMPLETE_INPUT_NAME.'" name="'.self::AUTOCOMPLETE_INPUT_NAME.'" />';
			echo '</label>';

			echo '<ul></ul>';

		echo '</div>';
	}

	/**
	 * @param $post_id
	 */
	public function save_post($post_id){

		if(!current_user_can("edit_post", $post_id)) return;

		if(!isset($_POST[self::NEW_USER_ID]) || !isset($_POST[self::NEW_TYPE_STATE_ID])) return;

		$user_ids = $_POST[self::NEW_USER_ID];
		$typeState_ids = $_POST[self::NEW_TYPE_STATE_ID];

		if(!is_array($user_ids) || !is_array($typeState_ids) || count($typeState_ids) != count($user_ids)) return;

		$user_ids = array_map(function($id){ return intval($id); }, $user_ids);
		$typeState_ids = array_map(function($id){ return intval($id); }, $typeState_ids);

		for($i = 0; $i < count($user_ids); $i++){
			Database\addRelationWithTypeState($user_ids[$i], $post_id, $typeState_ids[$i]);
		}

	}
}