<?php

 /**
  * The plugin bootstrap file
  *
  * @link              https://robertdevore.com
  * @since             1.0.0
  * @package           Widget_Usage_Tracker_For_Elementor
  *
  * @wordpress-plugin
  *
  * Plugin Name: Widget Usage Tracker for Elementor
  * Description: Displays all registered Elementor widgets and their usage count on the site, with a modal that shows links to the content where the widgets are found.
  * Plugin URI:  https://github.com/robertdevore/widget-usage-tracker-for-elementor/
  * Version:     1.0.0
  * Author:      Robert DeVore
  * Author URI:  https://robertdevore.com/
  * License:     GPL-2.0+
  * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
  * Text Domain: widget-usage-tracker-for-elementor
  * Domain Path: /languages
  * Update URI:  https://github.com/robertdevore/widget-usage-tracker-for-elementor/
  */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/robertdevore/widget-usage-tracker-for-elementor/',
	__FILE__,
	'widget-usage-tracker-for-elementor'
);

// Set the branch that contains the stable release.
$myUpdateChecker->setBranch( 'main' );

// Set the current version.
define( 'WUT_VERSION', '1.0.0' );

// Include WP_List_Table if it doesn't already already exist.
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Elementor_Widget_Usage_List_Table Class.
 *
 * Creates a WP_List_Table to display Elementor widgets and their usage details.
 */
class Elementor_Widget_Usage_List_Table extends WP_List_Table {

    private $widget_data = [];

    /**
     * Constructor for Elementor_Widget_Usage_List_Table.
     * 
     * @since  1.0.0
     * @return void
     */
    public function __construct() {
        parent::__construct(
            [
                'singular' => esc_html__( 'Widget', 'widget-usage-tracker-for-elementor' ),
                'plural'   => esc_html__( 'Widgets', 'widget-usage-tracker-for-elementor' ),
                'ajax'     => false,
            ]
        );
    }

    /**
     * Prepares the table items.
     * 
     * @since  1.0.0
     * @return void
     */
    public function prepare_items() {
        $this->widget_data = $this->get_widget_data();

        $columns = $this->get_columns();

        $this->_column_headers = [ $columns, [], $this->get_sortable_columns() ];
        $this->items           = $this->widget_data;
    }

    /**
     * Defines the table columns.
     *
     * @since  1.0.0
     * @return array Column headers for the table.
     */
    public function get_columns() {
        return [
            'name'    => esc_html__( 'Widget Type', 'widget-usage-tracker-for-elementor' ),
            'count'   => esc_html__( 'Usage Count', 'widget-usage-tracker-for-elementor' ),
            'details' => esc_html__( 'Details', 'widget-usage-tracker-for-elementor' ),
        ];
    }

    /**
     * Renders the column data for each row.
     *
     * @param array  $item        The current item.
     * @param string $column_name The name of the column.
     * 
     * @since  1.0.0
     * @return string The content for the column.
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'name':
                return esc_html( $item[ $column_name ] );
            case 'count':
                return esc_html( $item[ $column_name ] );
            case 'details':
                return sprintf(
                    '<a href="#" class="view-details" data-widget-name="%s">%s</a>',
                    esc_attr( $item['name'] ),
                    esc_html__( 'View Details', 'widget-usage-tracker-for-elementor' )
                );
            default:
                return print_r( $item, true );
        }
    }

    /**
     * Retrieves data for all registered Elementor widgets with usage count greater than zero.
     *
     * @since  1.0.0
     * @return array List of widgets with usage details.
     */
    private function get_widget_data() {
        if ( ! did_action( 'elementor/loaded' ) ) {
            return [];
        }

        $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
        $widgets         = $widgets_manager->get_widget_types();

        $widget_data = [];

        // Loop through the registered widgets.
        foreach ( $widgets as $widget_name => $widget_instance ) {
            $count = $this->count_widget_usage( $widget_instance->get_name() );
            if ( $count > 0 ) {
                $widget_data[] = [
                    'name'  => $widget_instance->get_name(),
                    'count' => $count,
                ];
            }
        }

        return $widget_data;
    }

    /**
     * Counts the usage of a specific Elementor widget across the site.
     *
     * @param string $widget_name The name of the widget to count.
     * 
     * @since  1.0.0
     * @return int The number of times the widget is used.
     */
    private function count_widget_usage( $widget_name ) {
        global $wpdb;
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}postmeta WHERE meta_key = '_elementor_data' AND meta_value LIKE %s",
                '%' . $wpdb->esc_like( $widget_name ) . '%'
            )
        );
        return $count ? (int) $count : 0;
    }
}

/**
 * Adds a menu item for the settings page.
 * 
 * @since  1.0.0
 * @return void
 */
function elementor_widget_usage_tracker_menu() {
    add_menu_page(
        esc_html__( 'Widget Usage Tracker for Elementor', 'widget-usage-tracker-for-elementor' ),
        esc_html__( 'Widget Usage', 'widget-usage-tracker-for-elementor' ),
        'manage_options',
        'widget-usage-tracker-for-elementor',
        'elementor_widget_usage_tracker_page'
    );
}
add_action( 'admin_menu', 'elementor_widget_usage_tracker_menu' );

/**
 * Displays the settings page content.
 * 
 * @since  1.0.0
 * @return void
 */
function elementor_widget_usage_tracker_page() {
    if ( ! class_exists( 'Elementor_Widget_Usage_List_Table' ) ) {
        return;
    }

    $table = new Elementor_Widget_Usage_List_Table();
    $table->prepare_items();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Widget Usage Tracker for Elementor', 'widget-usage-tracker-for-elementor' ); ?></h1>
        <form method="post">
            <?php $table->display(); ?>
        </form>
    </div>

    <div id="usage-details-modal" style="display:none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2><?php esc_html_e( 'Widget Usage Details', 'widget-usage-tracker-for-elementor' ); ?></h2>
            <p id="usage-header"></p>
            <ul id="usage-details-list"></ul>
        </div>
    </div>
    <?php
}

/**
 * Enqueue JavaScript for the modal and AJAX functionality.
 * 
 * @since  1.0.0
 * @return void
 */
function elementor_widget_usage_tracker_scripts() {
    wp_enqueue_style( 'widget-usage-tracker-css', plugin_dir_url( __FILE__ ) . 'assets/css/wut-styles.css', [], WUT_VERSION );
    // Enqueue the script.
    wp_enqueue_script( 'widget-usage-tracker-js', plugin_dir_url( __FILE__ ) . 'assets/js/wut-scripts.js', [ 'jquery' ], WUT_VERSION, true );
    // Localize the script.
    wp_localize_script( 'widget-usage-tracker-js', 'WidgetUsageTracker', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'widget_usage_tracker_nonce' ),
    ]);
}
add_action( 'admin_enqueue_scripts', 'elementor_widget_usage_tracker_scripts' );

/**
 * AJAX callback to get pages where the widget is used.
 * 
 * @since  1.0.0
 * @return void
 */
function elementor_widget_usage_tracker_ajax() {
    // Check the nonce.
    check_ajax_referer( 'widget_usage_tracker_nonce', 'nonce' );

    $widget_name = sanitize_text_field( $_POST['widget_name'] );

    global $wpdb;

    // Get the results from the DB.
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_data' AND meta_value LIKE %s",
            '%' . $wpdb->esc_like( $widget_name ) . '%'
        )
    );

    $pages = [];

    // Loop through the results.
    foreach ( $results as $result ) {
        $page = get_post( $result->post_id );
        if ( $page ) {
            $pages[] = [
                'title' => get_the_title( $page ),
                'url'   => get_permalink( $page ),
            ];
        }
    }

    wp_send_json_success( $pages );
}
add_action( 'wp_ajax_get_widget_usage_details', 'elementor_widget_usage_tracker_ajax' );
