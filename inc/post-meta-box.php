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

	const POST_USER_IDS = "cur_user_ids";

	const POST_RELATION_TYPESTATE_IDS = "cur_typestate_ids";

	const POST_ACTIONS = "cur_actions";

	const CUR_ACTION_DELETE = "delete";

	const CUR_ACTION_ADD = "add";

	const POST_READY_TO_SAVE = "cur_ready_to_save";

	const READY_TO_SAVE_VALUE = "ready-it-is";


	const APP_ROOT_ID = "cur-app";

	const AUTOCOMPLETE_APP_ROOT_ID = "cur-user-autocomplete";

	const AUTOCOMPLETE_INPUT_NAME = "cur_user_autocomplete_input";

	const NEW_USER_ID = "cur_new_user_id";

	const NEW_TYPE_STATE_ID = "cur_new_type_state_id";

	const DELETE_RELATION = "cur_delete_relation";

	/**
	 * PostMetaBox constructor.
	 *
	 * @param \ContentUserRelations\Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ) );
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

			$tyeStates = getRelationTypeStatesList();

			wp_localize_script( Plugin::HANDLE_POST_META_BOX_JS,
				"ContentUserRelations_MetaBox",
				array(
					'relations'               => $this->getPostRelationsGroupByUser( $post->ID ),
					'type_states'             => $tyeStates,
					'POST'                    => array(
						'user_ids'      => self::POST_USER_IDS,
						'typestate_ids' => self::POST_RELATION_TYPESTATE_IDS,
						'ready_to_save' => self::POST_READY_TO_SAVE,
						'actions'       => self::POST_ACTIONS,
					),
					'ACTION'                  => array(
						'delete' => self::CUR_ACTION_DELETE,
						'add'    => self::CUR_ACTION_ADD,
					),
					'ready_to_save_value'     => self::READY_TO_SAVE_VALUE,
					'app_root_id'             => self::APP_ROOT_ID,
					'root_id'                 => self::AUTOCOMPLETE_APP_ROOT_ID,
					'autocomplete_input_name' => self::AUTOCOMPLETE_INPUT_NAME,
					'name_user_id_arr'        => self::NEW_USER_ID,
					'name_type_state_id_arr'  => self::NEW_TYPE_STATE_ID,
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

	private function getPostRelationsGroupByUser( $post_id ) {
		$relations      = new Database\Query( array(
			"post_id" => $post_id,
		) );
		$user_relations = array();
		foreach ( $relations->getUserMap() as $user_id => $relations ) {
			$item                 = array();
			$user                 = get_user_by( "ID", $user_id );
			$item['user_id']      = $user_id;
			$item['display_name'] = $user->display_name;
			$item['relations']    = array();
			foreach ( $relations as $relation ) {
				$item['relations'][] = $relation;
			}

			$user_relations[] = $item;

		}

		return $user_relations;
	}

	/**
	 * render content
	 */
	function render() {

		$relations = getRelationTypeStatesList();

		if ( count( $relations ) < 1 ) {
			echo "<p class='description'>No relations configured.</p>";

			return;
		}

		echo '<div id="' . self::AUTOCOMPLETE_APP_ROOT_ID . '">';

		echo "<label class='cur-relation-type-label' for='cur-state-type-select'>Relation: ";
		echo "<select id='cur-state-type-select'>";
		foreach ( $relations as $rts ) {
			$name = $rts->type_name . " - " . $rts->state_name;
			echo "<option value='{$rts->id}'>$name</option>";
		}
		echo "</select>";
		echo "</label>";

		echo '<label for="' . self::AUTOCOMPLETE_INPUT_NAME . '">User: ';
		echo '<input type="text" id="' . self::AUTOCOMPLETE_INPUT_NAME . '" name="' . self::AUTOCOMPLETE_INPUT_NAME . '" />';
		echo '</label>';

		echo '<ul></ul>';

		echo '</div>';

		echo '<div id="' . self::APP_ROOT_ID . '"></div>';
	}

	/**
	 * @param $post_id
	 */
	public function save_post( $post_id ) {

		if ( ! current_user_can( "edit_post", $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::POST_READY_TO_SAVE ] ) || $_POST[ self::POST_READY_TO_SAVE ] != self::READY_TO_SAVE_VALUE ) {
			// perhaps something is wrong with javascript rendering.
			// to prevent loss of data ignore changes
			return;
		}

		if ( ! isset( $_POST[ self::POST_USER_IDS ] ) || ! isset( $_POST[ self::POST_RELATION_TYPESTATE_IDS ] ) || ! isset( $_POST[ self::POST_ACTIONS ] ) ) {
			return;
		}
		$user_ids      = $_POST[ self::POST_USER_IDS ];
		$typeState_ids = $_POST[ self::POST_RELATION_TYPESTATE_IDS ];
		$actions       = $_POST[ self::POST_ACTIONS ];

		if (
			! is_array( $user_ids ) || ! is_array( $typeState_ids ) || ! is_array( $actions )
			|| count( $typeState_ids ) != count( $user_ids ) || count( $actions ) != count( $user_ids )
		) {
			// parallel arrays need same size. else something went wrong
			return;
		}

		// sanitize values
		$user_ids      = array_map( function ( $id ) {
			return intval( $id );
		}, $user_ids );
		$typeState_ids = array_map( function ( $id ) {
			return intval( $id );
		}, $typeState_ids );
		$actions       = array_map( function ( $action ) {
			return sanitize_text_field( $action );
		}, $actions );

		for ( $i = 0; $i < count( $actions ); $i ++ ) {
			$action = $actions[ $i ];
			if ( $action == self::CUR_ACTION_ADD ) {
				Database\addRelationWithTypeState( $user_ids[ $i ], $post_id, $typeState_ids[ $i ] );
			} else if ( $action == self::CUR_ACTION_DELETE ) {
				Database\removeRelationWithTypeState( $user_ids[ $i ], $post_id, $typeState_ids[ $i ] );
			}
		}

		// @deprecated section

		if ( ! isset( $_POST[ self::NEW_USER_ID ] ) || ! isset( $_POST[ self::NEW_TYPE_STATE_ID ] ) ) {
			return;
		}

		$user_ids      = $_POST[ self::NEW_USER_ID ];
		$typeState_ids = $_POST[ self::NEW_TYPE_STATE_ID ];

		if ( ! is_array( $user_ids ) || ! is_array( $typeState_ids ) || count( $typeState_ids ) != count( $user_ids ) ) {
			return;
		}

		$user_ids      = array_map( function ( $id ) {
			return intval( $id );
		}, $user_ids );
		$typeState_ids = array_map( function ( $id ) {
			return intval( $id );
		}, $typeState_ids );

		for ( $i = 0; $i < count( $user_ids ); $i ++ ) {
			Database\addRelationWithTypeState( $user_ids[ $i ], $post_id, $typeState_ids[ $i ] );
		}

	}
}