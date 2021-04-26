<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 30.01.18
 * Time: 14:23
 */

namespace ContentUserRelations;


class UserProfile {

	/**
	 * UserProfile constructor.
	 *
	 * @param \ContentUserRelations\Plugin $plugin
	 */
	public function __construct( Plugin $plugin ) {
		$this->plugin = $plugin;
		add_action('init', array($this, 'init'));
	}

	function init(){
		// disable relations editor on user profile
		if(!apply_filters(Plugin::FILTER_EDIT_ON_USER_PROFILE, true)) return;

		if($this->plugin->settings->currentUserCanModify() && is_admin()){
			// only for authorized users
			add_action( 'admin_enqueue_scripts', array(
				$this,
				'user_profile_js',
			) );
			// current user profile page
			add_action( 'show_user_profile', array( $this, 'render' ) );
			// some user profile page
			add_action( 'edit_user_profile', array( $this, 'render' ) );

			add_action( 'personal_options_update', array(
				$this,
				'save_relation',
			) );
			add_action( 'edit_user_profile_update', array(
				$this,
				'save_relation',
			) );
		}
	}

	/**
	 * add js on profile screen
	 *
	 * @param $hook
	 */
	function user_profile_js( $hook ) {
		if ( "user-edit.php" == $hook || "profile.php" == $hook ) {
			wp_enqueue_style(
				"content-user-relations-user-profile-style",
				$this->plugin->url . "/css/user-profile.css"
			);
			wp_enqueue_script( 'jquery-ui-autocomplete' );
			wp_enqueue_script( Plugin::HANDLE_API_JS );
			wp_enqueue_script(
				Plugin::HANDLE_USER_PROFILE_JS,
				$this->plugin->url . "/js/user-profile.js",
				array(
					'jquery',
					'jquery-ui-autocomplete',
					Plugin::HANDLE_API_JS,
				)
			);
			wp_localize_script(
					Plugin::HANDLE_USER_PROFILE_JS,
					"ContentUserRelations_Profile",
					array(
						"test" => 1,
					)
			);
		}
	}

	/**
	 * @param \WP_User $user
	 */
	function render($user) {
		echo "<h2>".__('Content relations', Plugin::DOMAIN)."</h2>";

		?>
		<table id="cur-table" class="form-table">

			<tr class="cur-row">
				<th>
					<label for="cur-content-autocomplete">
						<?php
						_e('Add relation to content', Plugin::DOMAIN);
						?>
					</label>
				</th>
				<td>
					<input type="text" id="cur-content-autocomplete"/>
					<input type="hidden" id="cur-new-relation-content-id"
					       name="cur-new-relation-content-id" value=""/>
					<select name="cur-new-relation-typestate-id">
						<option value=""><?php _e('Please choose a relation type', Plugin::DOMAIN); ?></option>
						<?php
						$types = Database\getRelationTypes();
						foreach ( $types as $type ) {
							$typeName = $type->name;
							$typeSlug = $type->slug;
							echo "<optgroup label='$typeName [$typeSlug]'>";
							$states = Database\getRelationStates( $type->id );
							foreach ( $states as $state ) {
								$typestateId = Database\getTypeStateId( $type->slug, $state->slug );
								$stateName   = $state->name;
								$stateSlug   = $state->slug;
								echo "<option value='$typestateId'>$stateName [$stateSlug]</option>";

							}
							echo "</optgroup>";
						}
						?>
					</select>
					<p class="description">
						<?php _e('Save profile to add relation.',Plugin::DOMAIN) ?>
					</p>
				</td>
			</tr>

			<?php

			$args = array(
				"post_type" => "any",
				WPPostQueryExtension::ARG_USER_RELATABLE => true,
			);
			$args[WPPostQueryExtension::ARG_RELATED_TO_USER] = $user->ID;
			$query = new \WP_Query($args);

			while($query->have_posts()){
				$query->the_post();
				?>
				<tr class="cur-row">
					<th><a href="<?php echo get_edit_post_link(); ?>"><?php the_title(); ?></a></th>
					<td data-content-id="<?php the_ID(); ?>">
						<?php
							$relationsQuery = new Database\Query(array(
								"user_id" => $user->ID,
								"post_id" => get_the_ID(),
							));
							$map = $relationsQuery->getTypeMap();
							foreach ($map as $typeSlug => $relations){
								echo "<div class='cur-type'>".$relations[0]->type_name."</div>";
								echo "<div class='cur-states'>".implode(", ", array_map(function($rel){
									$attr = array(
										"data-post-id='".$rel->post_id."'",
										"data-type-slug='".$rel->type_slug."'",
										"data-state-slug='".$rel->state_slug."'",
									);
									return "<span class='cur-states__item' ".implode(" ", $attr).">".$rel->state_name."</span>";
								}, $relations))."</div>";
							}
						?>
					</td>
				</tr>
				<?php
			}
			?>

		</table>
		<?php
	}

	function save_relation( $user_id ) {
		if (
			isset( $_POST["cur-new-relation-content-id"] )
			&&
			isset( $_POST["cur-new-relation-typestate-id"] )
		) {
			$content_id = intval( $_POST["cur-new-relation-content-id"] );
			$typestate_id = intval( $_POST["cur-new-relation-typestate-id"] );

			if ( $content_id > 0 && $typestate_id > 0 ) {
				Database\addRelationWithTypeState( $user_id, $content_id, $typestate_id );
			}
		}
		if(
			isset( $_POST["cur_delete_relation_content"])
			&&
			isset( $_POST["cur_delete_relation_type"])
			&&
			isset( $_POST["cur_delete_relation_state"])
		){
			$contents = $_POST["cur_delete_relation_content"];
			$types = $_POST["cur_delete_relation_type"];
			$states = $_POST["cur_delete_relation_state"];
			if(!is_array($contents) || !is_array($types) || !is_array($states)) return;

			foreach ($contents as $idx => $content_id){
				Database\removeRelation($user_id, $content_id, $types[$idx], $states[$idx] );
			}

		}
	}



}