<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 30.01.18
 * Time: 14:23
 */

namespace ContentUserRelations;


class PostMetaBox {

	/**
	 * PostMetaBox constructor.
	 *
	 * @param \ContentUserRelations\Plugin $plugin
	 */
	public function __construct(Plugin $plugin) {
		$this->plugin = $plugin;
		$this->plugin = $plugin;
		add_action( 'add_meta_boxes', array($this,'add_meta_box'),10 ,2 );
	}

	/**
	 * register meta box
	 */
	function add_meta_box($post_type, $post){
		if($this->plugin->settings->isPostTypeEnabled($post_type)){
			wp_enqueue_style(
				"content-user-relations-style",
				$this->plugin->url . "/css/meta-box.css"
			);
			add_meta_box(
				'content-user-relations',
				__( 'Content user relations', Plugin::DOMAIN ),
				array($this,'render'),
				$post_type
			);
		}
	}

	/**
	 * render content
	 */
	function render(){

		$relations = new Database\Query(array(
			"post_id" => get_the_ID(),
		));

		$args = array();

		$args['singular'] = "Related Member";
        $args['plural'] = "Related Members";

        $columns = array();
        $columns['name'] = __('User', 'ph');
        $columns['groups'] = __('Group', 'ph');

        $sortable_columns = array();
        $sortable_columns['name'] = array( 'name', true );

		$items = array();

		foreach ($relations->getUserMap() as $user_id => $relations){
		    $item = array();
			$user = get_user_by("ID", $user_id);
			$item['user_id'] = $user_id;
			$item['user'] = $user;
			$item['name'] = $user->user_login;
			foreach($relations as $relation){
			    $item['relations'][] = $relation;
                $item['group'][] =  "<li>".$relation->type_name." – ".$relation->state_name."</li>";
            }

            $item['groups'] = "<ul>".implode(" ", $item['group'])."</ul>";
			$items[] = $item;

		}

        $table = new RenderTable($args, $columns, $sortable_columns, $items);
		$table->display();



	}
}