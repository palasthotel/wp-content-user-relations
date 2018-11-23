<?php
/**
 * Created by PhpStorm.
 * User: ben
 * Date: 19.11.18
 * Time: 13:13
 */

namespace ContentUserRelations;

class RenderTable{


    public $columns;

    public $sortable_columns;

    public $items;

    protected $column_cb;

    public function __construct($args, $columns, $sortable_columns, $items){

        $this->columns = $columns;
        $this->sortable_columns = $sortable_columns;
        $this->items = $items;

        $args = wp_parse_args( $args, array(
            'plural' => '',
            'singular' => '',
            'ajax' => false,
            'screen' => null,
        ) );

        $this->screen = convert_to_screen( $args['screen'] );

        add_filter( "manage_{$this->screen->id}_columns", array( $this, 'get_columns' ), 0 );

        if ( !$args['plural'] )
            $args['plural'] = $this->screen->base;

        $args['plural'] = sanitize_key( $args['plural'] );
        $args['singular'] = sanitize_key( $args['singular'] );

        $this->_args = $args;

        if ( $args['ajax'] ) {
            // wp_enqueue_script( 'list-table' );
            add_action( 'admin_footer', array( $this, '_js_vars' ) );
        }

        if ( empty( $this->modes ) ) {
            $this->modes = array(
                'list'    => __( 'List View' ),
                'excerpt' => __( 'Excerpt View' )
            );
        }

    }

    /**
     * @return array
     */
    public function get_column_count()
    {
        return count($this->columns);
    }

    /**
     * Message to be displayed when there are no items
     *
     * @since 3.1.0
     */
    public function no_items() {
        _e( 'No items found.' );
    }

    /**
     * Whether the table has items to display or not
     *
     * @since 3.1.0
     *
     * @return bool
     */
    public function has_items() {
        return !empty( $this->items );
    }

    /**
     * Get a list of CSS classes for the WP_List_Table table tag.
     *
     * @since 3.1.0
     *
     * @return array List of CSS classes for the table tag.
     */
    protected function get_table_classes() {
        return array( 'widefat', 'fixed', 'striped', $this->_args['plural'] );
    }



    protected function print_column_headers(){
        foreach ( $this->columns as $column_key => $column_display_name ) {
            $tag = ( 'cb' === $column_key ) ? 'td' : 'th';
            $scope = ( 'th' === $tag ) ? 'scope="col"' : '';
            echo "<$tag $scope>$column_display_name</$tag>";
        }

    }

    /**
     * Generate the tbody element for the list table.
     *
     * @since 3.1.0
     */
    public function display_rows_or_placeholder() {
        if ( $this->has_items() ) {
            $this->display_rows();
        } else {
            echo '<tr class="no-items"><td class="colspanchange" colspan="' . $this->get_column_count() . '">';
            $this->no_items();
            echo '</td></tr>';
        }
    }

    /**
     * Generate the table rows
     *
     * @since 3.1.0
     */
    public function display_rows() {
        foreach ( $this->items as $item )
            $this->single_row( $item );
    }

    /**
     * Generates content for a single row of the table
     *
     * @since 3.1.0
     *
     * @param object $item The current item
     */
    public function single_row( $item ) {
        echo '<tr>';
        $this->single_row_columns( $item );
        echo '</tr>';
    }

    /**
     * Generates the columns for a single row of the table
     *
     * @since 3.1.0
     *
     * @param object $item The current item
     */
    protected function single_row_columns( $item ) {
        $columns = $this->columns;
        foreach ($this->columns as $column){
            $primary = $column;
            break;
        }
        $hidden = array();

        foreach ( $columns as $column_name => $column_display_name ) {
            $classes = "$column_name column-$column_name";
            if ( $primary === $column_name ) {
                $classes .= ' has-row-actions column-primary';
            }

            if ( in_array( $column_name, $hidden ) ) {
                $classes .= ' hidden';
            }

            // Comments column uses HTML in the display name with screen reader text.
            // Instead of using esc_attr(), we strip tags to get closer to a user-friendly string.
            $data = 'data-colname="' . wp_strip_all_tags( $column_display_name ) . '"';

            $attributes = "class='$classes' $data";

            if ( 'cb' === $column_name ) {
                echo '<th scope="row" class="check-column">';
                echo $this->column_cb( $item );
                echo '</th>';
            } elseif ( method_exists( $this, '_column_' . $column_name ) ) {
                echo call_user_func(
                    array( $this, '_column_' . $column_name ),
                    $item,
                    $classes,
                    $data,
                    $primary
                );
            } elseif ( method_exists( $this, 'column_' . $column_name ) ) {
                echo "<td $attributes>";
                echo call_user_func( array( $this, 'column_' . $column_name ), $item );
                //echo $this->handle_row_actions( $item, $column_name, $primary );
                echo "</td>";
            } else {
                echo "<td $attributes>";
                echo $this->column_default( $item, $column_name );
                //echo $this->handle_row_actions( $item, $column_name, $primary );
                echo "</td>";
            }
        }
    }

    /**
     *
     * @param object $item
     * @param string $column_name
     */
    protected function column_default( $item, $column_name ) {
        return $item[ $column_name ];
    }


    /**
     * Display the table
     *
     * @since 3.1.0
     */
    public function display()
    {
        $singular = $this->_args['singular'];

        $this->screen->render_screen_reader_content('heading_list');
        ?>
        <table class="wp-list-table <?php echo implode(' ', $this->get_table_classes()); ?>">
            <thead>
            <tr>
                <?php $this->print_column_headers(); ?>
            </tr>
            </thead>

            <tbody id="the-list"<?php
            if ($singular) {
                echo " data-wp-lists='list:$singular'";
            } ?>>
            <?php $this->display_rows_or_placeholder(); ?>
            </tbody>

            <tfoot>
            <tr>
                <?php $this->print_column_headers(false); ?>
            </tr>
            </tfoot>

        </table>
        <?php

    }

}