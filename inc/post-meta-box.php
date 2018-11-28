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

	const CUR_ACTION_REMOVE = "remove";

	const CUR_ACTION_ADD = "add";

	const POST_READY_TO_SAVE = "cur_ready_to_save";

	const READY_TO_SAVE_VALUE = "ready-it-is";


	const APP_ROOT_ID = "cur-app";

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
					'i18n' => array(
						'col_title_user' => __('User', Plugin::DOMAIN),
						'col_title_relations' => __('Relations', Plugin::DOMAIN),
						'no_relations_found' => __('No relations found', Plugin::DOMAIN),
						'label_add_user_control' => __('Add user', Plugin::DOMAIN),
						'label_typestate_select' => __('Relation', Plugin::DOMAIN),
						'label_autocomplete_users' => __('User', Plugin::DOMAIN),
						'remove' => __('Remove', Plugin::DOMAIN),
						'unremove' => __('Don\'t remove', Plugin::DOMAIN),
					),
					'relations'               => $this->getPostRelationsGroupByUser( $post->ID ),
					'typestates'             => $tyeStates,
					'POST'                    => array(
						'user_ids'      => self::POST_USER_IDS,
						'typestate_ids' => self::POST_RELATION_TYPESTATE_IDS,
						'ready_to_save' => self::POST_READY_TO_SAVE,
						'actions'       => self::POST_ACTIONS,
					),
					'ACTION'                  => array(
						'remove' => self::CUR_ACTION_REMOVE,
						'add'    => self::CUR_ACTION_ADD,
					),
					'links' => array(
						'user_profile' => add_query_arg( 'user_id', '%uid%', self_admin_url( 'user-edit.php' ) ),
					),
					'ready_to_save_value'     => self::READY_TO_SAVE_VALUE,
					'app_root_id'             => self::APP_ROOT_ID,
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
			} else if ( $action == self::CUR_ACTION_REMOVE ) {
				Database\removeRelationWithTypeState( $user_ids[ $i ], $post_id, $typeState_ids[ $i ] );
			}
		}

	}
}