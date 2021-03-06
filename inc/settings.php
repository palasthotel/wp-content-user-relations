<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 30.01.18
 * Time: 14:31
 */

namespace ContentUserRelations;


class Settings {

	const PARENT_SLUG = "users.php";
	const MENU_SLUG = "content-user-relation-settings";
	const PARAM_TYPE = "cur_type";
	const PARAM_STATE = "cur_state";

	public function __construct( Plugin $plugin ) {
		// post types whitelist (for meta box for example)
		// add types and states

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * check if current user is allowed to change settings
	 * @return bool
	 */
	function currentUserCanModify(){
		return current_user_can("delete_posts");
	}

	/**
	 * get all post types enabled
	 * @return array
	 */
	function getPostTypesEnabled(){
		return get_option("_cur_post_types_enabled", array());
	}

	/**
	 * @param array("post",...) $post_type
	 */
	function setPostTypesEnabled($post_types_array){
		if($this->currentUserCanModify()) update_option("_cur_post_types_enabled", $post_types_array);
	}

	/**
	 * @param string $post_type
	 *
	 * @return bool
	 */
	function isPostTypeEnabled($post_type){
		$types_enabled = $this->getPostTypesEnabled();
		if(!is_array($types_enabled)) return false;
		return array_search($post_type, $types_enabled) !== false;
	}

	/**
	 * redirect post requests in init so on reload there is no alert to rerun
	 * the post request
	 * (that's the wordpress way)
	 */
	function init() {

		// if not allowed... get the hell out of here!
		if(!$this->currentUserCanModify()) return;

		// post types
		if ( isset( $_POST["save_post_types"] ) ) {
			if(!is_array($_POST["post_types"])) return;
			$active_post_types = $_POST["post_types"];
			$this->setPostTypesEnabled($active_post_types);
			$url = add_query_arg( array(
				"page" => self::MENU_SLUG,
				// TODO: redirect to edit page? "cur_type" => $type->id,
			), admin_url( self::PARENT_SLUG ) );
			wp_redirect( $url );
		}
		// new relation type
		if ( isset( $_POST["submit_new_type"] ) ) {
			$slug = urldecode( rtrim( sanitize_title( $_POST["cur_slug"] ) ) );
			$name = rtrim( sanitize_text_field( $_POST["cur_name"] ) );
			Database\addRelationType( $slug, $name );
			$url = add_query_arg( array(
				"page" => self::MENU_SLUG,
				// TODO: redirect to edit page? "cur_type" => $type->id,
			), admin_url( self::PARENT_SLUG ) );
			wp_redirect( $url );
		}
		// new relation state
		if ( isset( $_POST["submit_new_state"] ) ) {
			$slug = urldecode( rtrim( sanitize_title( $_POST["cur_slug"] ) ) );
			$name = rtrim( sanitize_text_field( $_POST["cur_name"] ) );
			Database\addRelationState( $slug, $name );
			$url = add_query_arg( array(
				"page" => self::MENU_SLUG,
				// TODO: redirect to edit page? "cur_state" => $state->id,
			), admin_url( self::PARENT_SLUG ) );
			wp_redirect( $url );
		}
		// add state to type
		if(isset($_POST["submit_type_states"])){
			$type_id = intval($_POST["type_id"]);
			if($type_id < 1) return;
			$new_states = $_POST["states"];
			if(!is_array($new_states)) return;

			$all_states = Database\getRelationStates();
			$old_states = Database\getRelationStates($type_id);
			$changes = (object)array(
				"delete" => array(),
				"add" => array(),
			);
			foreach ($all_states as $state){
				$isInNewStates = array_search($state->id, $new_states) !== false;
				$isInOldStates = array_search($state->id, array_column($old_states, 'id')) !== false;
				if( $isInNewStates && !$isInOldStates	){
					// if in active states and not in old_states add it!
					$changes->add[] = $state->id;
				} else if( !$isInNewStates && $isInOldStates ){
					// if not in active states but in old_states delete it
					$changes->delete[] = $state->id;
				}
			}
			foreach($changes->add as $state_id){
				Database\addRelationTypeState($type_id, $state_id);
			}
			foreach ($changes->delete as $state_id){
				Database\removeRelationTypeState($type_id, $state_id);
			}
		}
		// add type to state
		if(isset($_POST["submit_state_types"])){
			$state_id = intval($_POST["state_id"]);
			if($state_id < 1) return;
			$new_types = $_POST["types"];
			if(!is_array($new_types)) return;

			$all_types = Database\getRelationTypes();
			$old_types = Database\getRelationTypes($state_id);
			$changes = (object)array(
				"delete" => array(),
				"add" => array(),
			);
			foreach ($all_types as $type){
				$isInNewStates = array_search($type->id, $new_types) !== false;
				$isInOldStates = array_search($type->id, array_column($old_types, 'id')) !== false;
				if( $isInNewStates && !$isInOldStates	){
					// if in active states and not in old_states add it!
					$changes->add[] = $type->id;
				} else if( !$isInNewStates && $isInOldStates ){
					// if not in active states but in old_states delete it
					$changes->delete[] = $type->id;
				}
			}
			foreach($changes->add as $type_id){
				Database\addRelationTypeState($type_id, $state_id);
			}
			foreach ($changes->delete as $type_id){
				Database\removeRelationTypeState($type_id, $state_id);
			}
		}
	}

	/**
	 * register admin menu paths
	 */
	public function admin_menu() {
		if($this->currentUserCanModify()){
			add_submenu_page(
				self::PARENT_SLUG,
				_x( 'Content relations ‹ User', 'Menu', Plugin::DOMAIN ),
				_x( 'Content relations', 'Menu', Plugin::DOMAIN ),
				'manage_options',
				self::MENU_SLUG,
				array( $this, 'render_settings' )
			);
		}
	}

	function render_settings() {
		echo '<div class="wrap">';
		if ( isset( $_GET[ self::PARAM_TYPE ] ) ) {
			$this->renderBackToOverview(_x("Relation type",'settings', Plugin::DOMAIN));
			$this->renderTypeStates( intval( $_GET[ self::PARAM_TYPE ] ) );
		} else if( isset( $_GET[ self::PARAM_STATE ] ) ){
			$this->renderBackToOverview(_x("Typestate",'settings', Plugin::DOMAIN));
			$this->renderStateTypes( intval( $_GET[ self::PARAM_STATE ] ) );
		} else {


			$this->renderTypes();
			echo "<hr>";
			$this->renderStates();
			echo "<hr>";
			$this->renderPostTypes();
		}
		echo '</div>';
	}

	function renderPostTypes(){
		?>
		<h2><?php _e('Post types', Plugin::DOMAIN); ?></h2>
		<p><?php _e('Show meta box for the following post types.', Plugin::DOMAIN); ?></p>
		<form method="post">
		<?php
		$post_types = get_post_types( array(

		), 'objects' );

		foreach ($post_types as $post_type){
			$name = $post_type->name;
			$label = $post_type->label;
			$checked = ($this->isPostTypeEnabled($name))? "checked": "";
			echo "<label>";
			echo "<input type='checkbox' $checked value='$name' name='post_types[]' /> $label <small>[$name]</small> ";
			echo "</label><br>";
		}
		submit_button(__("Save", Plugin::DOMAIN), 'primary', 'save_post_types');
		?>
		</form>
		<?php
	}

	function renderTypes() {
		?>
		<h2><?php _e('Relation types',Plugin::DOMAIN) ?></h2>
		<ul>
			<?php
			$types = Database\getRelationTypes();
			foreach ( $types as $type ) {
				$name = $type->name;
				$slug = $type->slug;
				$link = add_query_arg( array(
					"page"           => self::MENU_SLUG,
					self::PARAM_TYPE => $type->id,
				), admin_url( self::PARENT_SLUG ) );
				echo "<li><a href='$link'>$name</a> <small>[$slug]</small></li>";
			}
			?>
		</ul>
		<form method="post">
			<h3><?php _e('New type', Plugin::DOMAIN) ?></h3>
			<label>
				Name
				<input type="text" name="cur_name"/>
			</label>
			<label>
				Slug
				<input type="text" name="cur_slug"/>
			</label>
			<?php submit_button( __("Add", Plugin::DOMAIN), "primary", "submit_new_type", false ); ?>
		</form>
		<?php
	}

	function renderStates() {
		?>
		<h2><?php _e('Typestates', Plugin::DOMAIN); ?></h2>
		<ul>
			<?php
			$states = Database\getRelationStates();
			foreach ( $states as $state ) {
				$name = $state->name;
				$slug = $state->slug;
				$link = add_query_arg( array(
					"page"           => self::MENU_SLUG,
					self::PARAM_STATE => $state->id,
				), admin_url( self::PARENT_SLUG ) );
				echo "<li><a href='$link'>$name</a> <small>[$slug]</small></li>";
			}
			?>
		</ul>
		<form method="post">
			<h3><?php _e('New state', Plugin::DOMAIN); ?></h3>
			<label>
				Name
				<input type="text" name="cur_name"/>
			</label>
			<label>
				Slug
				<input type="text" name="cur_slug"/>
			</label>
			<?php submit_button( __("Add", Plugin::DOMAIN), "primary", "submit_new_state", false ); ?>
		</form>
		<?php
	}

	function renderTypeStates( $type_id ) {

		$type     = Database\getRelationType($type_id, 'id');
		$typeName = $type->name;
		$typeSlug = $type->slug;
		echo "<h2>$typeName <small>[$typeSlug]</small></h2>";

		?>
		<form method="post">
			<input type="hidden" value="<?php echo $type->id; ?>" name="type_id" />
			<?php

			$states      = Database\getRelationStates();
			$type_states = Database\getRelationStates( $type->id );
			echo "<ul>";
			foreach ( $states as $state ) {
				$stateId = $state->id;
				$stateName   = $state->name;
				$stateSlug = $state->slug;
				$checked     = array_search( $state->id, array_column( $type_states, 'id' ) );
				$checked     = ( $checked === false ) ? "" : "checked data-delete-warning";
				echo "<label>";
				echo "<input type='checkbox' $checked value='$stateId' name='states[]' /> $stateName <small>[$stateSlug]</small> ";
				echo "</label>";
			}
			echo "</ul>";

			submit_button( __("Save states for type", Plugin::DOMAIN), 'primary', 'submit_type_states' );
			?>
			<script>
				(function($){
					$(function(){
						$("body").on("change","input[data-delete-warning]", function(e){
							if(!$(this).prop("checked")){
								alert(
									"<?php _e('If you uncheck a state and save all user content relations to this type state relation will get lost 🚨!',Plugin::DOMAIN); ?>"
								);
							}
						});
					});
				})(jQuery);

			</script>
		</form>
		<?php
	}

	function renderStateTypes( $state_id ) {

		$state     = Database\getRelationState($state_id, 'id');
		$name = $state->name;
		$slug = $state->slug;

		echo "<h2>$name [$slug]</h2>";

		?>
		<form method="post">
			<input type="hidden" value="<?php echo $state->id; ?>" name="state_id" />
			<?php

			$types      = Database\getRelationTypes();
			$state_types = Database\getRelationTypes( $state->id );
			echo "<ul>";
			foreach ( $types as $type ) {
				$typeId = $type->id;
				$typeName   = $type->name;
				$typeSlug = $type->slug;
				$checked     = array_search( $type->id, array_column( $state_types, 'id' ) );
				$checked     = ( $checked === false ) ? "" : "checked data-delete-warning";
				echo "<label>";
				echo "<input type='checkbox' $checked value='$typeId' name='types[]' /> $typeName <small>[$typeSlug]</small> ";
				echo "</label>";
			}
			echo "</ul>";

			submit_button( "Save types for state", 'primary', 'submit_state_types' );
			?>
			<script>
				(function($){
					$(function(){
						$("body").on("change","input[data-delete-warning]", function(e){
							if(!$(this).prop("checked")){
								alert("If you uncheck a type and save all user content relations to this state type relation will get lost!");
							}
						});
					});
				})(jQuery);

			</script>
		</form>
		<?php

	}

	function renderBackToOverview($currentPage = ""){
		$current = ($currentPage != "")? " ‹ $currentPage": "";
		$url = add_query_arg( array(
			"page" => self::MENU_SLUG,
		), admin_url( self::PARENT_SLUG ) );
		echo "<p><a href='$url'>" . __( 'Back to overview', Plugin::DOMAIN ) . "</a>$current</p>";
	}
}